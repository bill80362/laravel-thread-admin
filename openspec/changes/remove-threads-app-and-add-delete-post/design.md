## Context

目前系統架構如下（僅列出與本次變更有關的部分）：

```
User
 └─ ThreadsApp (client_id, client_secret, user_id)
      └─ ThreadsAccount (threads_app_id, user_id, access_token)
           ├─ Post (threads_media_id, status)
           └─ Reply

OAuthState (token_hash, threads_app_id, threads_account_id, user_id)

routes/ai.php:
  Mcp::local('threads', ...)   ← 本地 Artisan 模式
  Mcp::web('/mcp/threads', ...) ← HTTP 模式
```

本次變更涉及：移除 ThreadsApp、OAuth state 重構、新增刪除貼文、MCP 模式簡化。

## Goals / Non-Goals

**Goals:**
- 簡化憑證管理：從資料庫多 App 改為 `.env` 單一設定
- 修復 OAuth callback 的 user_id 歸屬問題
- 實作完整的刪除貼文流程（含 Threads API 呼叫、狀態機、重試）
- MCP 僅保留 HTTP 模式，帳號唯讀

**Non-Goals:**
- 不改變 Post/Reply 的核心發佈邏輯
- 不改變 Threads OAuth 授權流程的基本結構（僅調整參數來源）
- 不新增 MCP 工具（僅移除本地模式與約束帳號唯讀）

## Decisions

### 1. Threads 憑證：config/services.php + .env

**選擇**：從 `config('services.threads')` 讀取 `client_id` / `client_secret`，值來自 `.env` 的 `THREADS_CLIENT_ID` / `THREADS_CLIENT_SECRET`。

**替代方案**：
- 直接 `env()` 呼叫：❌ 違反 Laravel 慣例，config caching 後會失效
- 保留單一 ThreadsApp 記錄：❌ 仍需 DB 查詢，沒有簡化效果

**影響**：`ThreadsClient` 所有方法不再接收 `ThreadsApp $app`，改為無參數或從 config 讀取。

### 2. OAuth 路由簡化

**選擇**：OAuth redirect 路由從 `GET /threads/oauth/{app}/redirect` 改為 `GET /threads/oauth/redirect`，移除 `{app}` 路徑參數。

**替代方案**：
- 保留 `{app}` 但忽略：❌ 混淆，路由語意不清

**影響**：`ThreadsOAuthController::redirect()` 不再接收 `ThreadsApp $app`；`ThreadsAccountsTable` 的重新授權 URL 也需調整。

### 3. OAuth state 攜帶 user_id

**選擇**：`OAuthState::resolve()` 回傳陣列中加入 `user_id`，callback 使用 `$resolved['user_id']` 而非 `auth()->id()`。

**替代方案**：
- 僅修復 `updateOrCreate` 查詢條件：❌ 只解決一半問題，session 失效時 `auth()->id()` 仍為 null
- 在 redirect URL 上附加 `user_id`：❌ 暴露 user_id 在 URL 中，安全性較差

**影響**：`OAuthState::resolve()` 回傳型別從 `array{app, account}` 改為 `array{user_id, account}`。`resolve()` 移除 `where('user_id', auth()->id())` 安全檢查，純靠 token hash（32 bytes 隨機 + SHA-256 + 10 分鐘過期 + 單次使用）驗證。

### 4. 刪除貼文狀態機

**選擇**：在 `PostStatus` 枚舉新增 `Deleting` 與 `DeleteFailed`，透過 `DeletePost` Job 處理非同步刪除。

```
Published ──(使用者點刪除)──▶ Deleting ──(DELETE /{media-id})──┬─▶ 成功 → $post->delete() + cascade 刪除 Reply
                                                               └─▶ 失敗 → DeleteFailed
                                                                      │
                                                                      └─▶ 再次觸發 → Deleting → ...
```

**DeletePost Job 不自動重試**：失敗直接標記 `DeleteFailed` + 寫入 `error_message`，由使用者手動再次觸發。Token 失效時額外將帳號設為 `NeedsReauth`。

**Cascade 刪除回覆**：貼文刪除成功時，透過 foreign key `onDelete('cascade')` 一併刪除該貼文的所有本地 Reply 記錄。

**替代方案**：
- 同步刪除（不經 Job）：❌ 使用者需等待 API 回應，體驗差
- 軟刪除（SoftDeletes）：❌ 需求明確要求成功才刪除記錄，失敗保留

**影響**：`PostStatus` 枚舉、`PostsTable`（Filament 刪除動作邏輯）、新增 `DeletePost` Job。

### 5. ThreadsClient::deleteMedia()

**選擇**：新增 `deleteMedia(ThreadsAccount $account, string $mediaId): bool`，呼叫 `DELETE /{threads-media-id}`，成功回傳 `true`，失敗拋出 `ThreadsApiException`。

**Threads API 端點**：`DELETE https://graph.threads.net/v1.0/{threads-media-id}?access_token=...`

### 6. MCP 本地模式移除

**選擇**：`routes/ai.php` 刪除 `Mcp::local(...)` 該行。

**影響**：使用說明（usage-guide chapter 5）需移除「本地模式」相關內容。

### 7. Migration 策略

**選擇**：建立一個新 migration 執行以下操作：
1. `threads_accounts` 移除 `threads_app_id` 外鍵與欄位
2. `threads_oauth_states` 移除 `threads_app_id` 外鍵與欄位
3. 刪除 `threads_apps` 表格

**注意**：舊的 migration 檔案（`create_threads_apps_table`、`add_threads_app_id_to_threads_accounts`、`seed_threads_apps_from_env`）保留不刪，因為它們是歷史記錄。新 migration 負責反向操作。

## Risks / Trade-offs

- **[風險] 多 App 能力喪失**：若未來需要多 App，需重新實作 → 目前需求明確為單一 App，且 `.env` 可隨時改回資料庫方案
- **[風險] 刪除 API 呼叫失敗率**：Threads API 可能因網路或服務端問題失敗 → 透過 `DeleteFailed` 狀態保留記錄，使用者可再次觸發刪除
- **[風險] Migration 在生產環境執行**：移除 `threads_app_id` 會影響現有資料 → 部署前需確保 `.env` 已設定正確憑證，且所有帳號已重新綁定（或手動遷移 `user_id`）
- **[風險] OAuth state 的 `user_id` 與 session 不同步**：若使用者在不同瀏覽器開啟授權 → state 中的 `user_id` 是建立 state 時的使用者，這是正確行為

## Migration Plan

1. **部署前**：在 `.env` 設定 `THREADS_CLIENT_ID` 與 `THREADS_CLIENT_SECRET`
2. **部署**：執行 `php artisan migrate`（新 migration 移除 `threads_apps` 表與相關外鍵）
3. **驗證**：確認 OAuth 綁定流程正常、現有帳號仍可發文
4. **Rollback**：若需回溯，migration 可反向執行重建 `threads_apps` 表，但資料需手動還原

## Open Questions

- 無

# 移除 ThreadsApp、修復 OAuth 綁定、新增刪除貼文、簡化 MCP

> 日期：2026-08-19
> 對應 OpenSpec change：`remove-threads-app-and-add-delete-post`

## 目的

簡化 Threads API 憑證管理（從資料庫多 App 改為 `.env` 單一設定）、修復 OAuth callback 的 user_id 歸屬問題、新增刪除已發佈 Threads 貼文功能（含狀態機與錯誤追蹤）、MCP 僅保留 HTTP 模式且帳號唯讀。

## 背景

- 目前 `ThreadsApp` 以資料庫表格管理多個 Meta App 憑證，但實際營運僅需單一 App，多 App 管理徒增複雜度。
- OAuth callback 綁定帳號時以 `auth()->id()` 判定使用者，且 `updateOrCreate` 僅以 `threads_user_id` 作為查詢條件，可能導致不同登入人員綁定同一 Threads 帳號時互相覆蓋。
- 系統缺少刪除已發佈 Threads 貼文的能力，營運人員無法下架貼文並追蹤刪除結果。
- MCP 本地（Artisan）模式下 `auth()->id()` 為 null，無法進行資料隔離，已無實際用途。

## 決策

### D1: Threads 憑證改由 config/services.php + .env 提供

從 `config('services.threads')` 讀取 `client_id` / `client_secret`，值來自 `.env` 的 `THREADS_CLIENT_ID` / `THREADS_CLIENT_SECRET`。

- 不直接 `env()` 呼叫（config caching 後會失效）
- 不保留單一 ThreadsApp 記錄（仍需 DB 查詢，沒有簡化效果）

### D2: OAuth 路由簡化

OAuth redirect 路由從 `GET /threads/oauth/{app}/redirect` 改為 `GET /threads/oauth/redirect`，移除 `{app}` 路徑參數。

### D3: OAuth state 攜帶 user_id

`OAuthState::resolve()` 回傳 `{user_id, account}`（移除 `app` 鍵），callback 使用 `$resolved['user_id']` 而非 `auth()->id()`。`resolve()` 移除 `where('user_id', auth()->id())` 安全檢查，純靠 token hash（32 bytes 隨機 + SHA-256 + 10 分鐘過期 + 單次使用）。

`updateOrCreate` 查詢條件加入 `user_id`（`threads_user_id` + `user_id`），防止跨登入人員覆蓋綁定。

### D4: 刪除貼文狀態機

在 `PostStatus` 枚舉新增 `Deleting`（刪除中）與 `DeleteFailed`（刪除失敗）。

```
Published ──(使用者點刪除)──▶ Deleting ──(DELETE /{media-id})──┬─▶ 成功 → $post->delete() + cascade replies
                                                               └─▶ 失敗 → DeleteFailed + error_message
                                                                      │
                                                                      └─▶ 再次觸發 → Deleting → ...
```

Filament 刪除動作邏輯：

| 貼文狀態 | 刪除行為 |
|----------|----------|
| `Draft` / `Scheduled` / `Publishing` / `Failed` | 直接刪除本地記錄 |
| `Published` | 設為 `Deleting` → dispatch `DeletePost` Job |
| `Deleting` | 隱藏刪除按鈕（正在刪除中） |
| `DeleteFailed` | 設為 `Deleting` → dispatch `DeletePost` Job（再次嘗試） |

### D5: DeletePost Job 不自動重試

失敗直接標記 `DeleteFailed` + 寫入 `error_message`，由使用者手動再次觸發。Token 失效時額外將帳號設為 `NeedsReauth`。

### D6: 刪除貼文成功時 cascade 刪除回覆

貼文從 Threads 刪除成功、本地記錄移除時，一併刪除該貼文的所有本地 Reply 記錄（透過 foreign key `onDelete('cascade')`）。

### D7: MCP 僅保留 HTTP 模式

`routes/ai.php` 移除 `Mcp::local('threads', ThreadsMcpServer::class)`，僅保留 `Mcp::web('/mcp/threads', ...)`。

### D8: MCP Threads 帳號唯讀

MCP 僅提供 `list-accounts`（唯讀），不提供新增／修改／刪除帳號的工具。此為約束性規則，防止未來誤加。

### D9: Migration 策略

建立一個新 migration 執行：
1. `threads_accounts` 移除 `threads_app_id` 外鍵與欄位
2. `threads_oauth_states` 移除 `threads_app_id` 外鍵與欄位
3. 刪除 `threads_apps` 表格

舊 migration 檔案保留不刪（歷史記錄）。

## 風險

- **多 App 能力喪失**：若未來需要多 App，需重新實作。目前需求明確為單一 App。
- **刪除 API 呼叫失敗率**：Threads API 可能因網路或服務端問題失敗 → 透過 `DeleteFailed` 狀態保留記錄，使用者可再次觸發。
- **Migration 在生產環境執行**：部署前需確保 `.env` 已設定正確憑證。

## 遷移計畫

1. **部署前**：在 `.env` 設定 `THREADS_CLIENT_ID` 與 `THREADS_CLIENT_SECRET`
2. **部署**：執行 `php artisan migrate`
3. **驗證**：確認 OAuth 綁定流程正常、現有帳號仍可發文
4. **Rollback**：migration 可反向執行重建 `threads_apps` 表，但資料需手動還原

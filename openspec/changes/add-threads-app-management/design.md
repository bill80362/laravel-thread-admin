## Context

動機見 proposal.md - Why。現況關鍵約束：

- `ThreadsClient` 目前所有「發文/回覆/抓回覆」方法都吃 `ThreadsAccount $account`、用 `$account->access_token`，**與 App 層級無關**；只有三個 OAuth 方法（`buildAuthorizationUrl`、`exchangeCodeForShortToken`、`exchangeShortForLongToken`）從 `Config::get('services.threads.*')` 讀取全域憑證。
- `ThreadsAccount` 已用 `encrypted` cast 儲存 `access_token`，可直接沿用於 `ThreadsApp::client_secret`。
- OAuth 回呼目前靠單一 session key `threads_oauth_state` 做 CSRF 防護，未承載任何上下文。
- 既有排程 Job（`RefreshThreadsTokens`、`CollectThreadsReplies`、`PublishScheduledPost`）皆透過 `ThreadsAccount` 運作，多 App 化後無需修改。

## Goals / Non-Goals

**Goals:**
- 引入 `ThreadsApp` 模型與表，讓登入人員管理多個 Meta App，`client_secret` 加密儲存。
- `threads_accounts` 掛上 `threads_app_id`，帳號歸屬 App。
- OAuth 由 App 發起，state 存 DB（帶過期時間）並承載「App 身分」與「可選的重新授權目標帳號」。
- `redirect_uri` 維持統一，由 `APP_URL` 推導，不進 DB。
- 提供資料遷移，將 `.env` 既有憑證落地為一筆 `threads_apps` 並關聯既有帳號。
- 補「重新授權」入口，`needs_reauth` 帳號可重新授權而不需解除綁定。

**Non-Goals:**
- 不做多租戶隔離（不同 User 之間不共享 App，也不做 RBAC 細粒度權限）。
- 不引入每個 App 獨立的 `redirect_uri`。
- 不改變發文/回覆/排程既有行為。

## Decisions

### Decision 1: 新增 `threads_apps` 表與 `ThreadsApp` Model
- **做法**：`threads_apps` 欄位：`id`、`user_id`（FK→users，nullable）、`name`、`client_id`、`client_secret`（text）、timestamps。`ThreadsApp` fillable 為上述欄位，`client_secret` 用 `encrypted` cast。
- **理由**：`client_secret` 屬敏感資訊，沿用 `ThreadsAccount` 已驗證的 `encrypted` cast 模式。
- **替代方案**：沿用 `.env` 多組設定（`THREADS_CLIENT_ID_1`...）。缺點是無法動態新增 App，且與「登入人員管理多 App」目標衝突。

### Decision 2: `user_id` 設為 nullable FK
- **做法**：`threads_apps.user_id` 用 `foreignId()->nullable()->constrained()->nullOnDelete()`。UI 只顯示 `user_id === auth()->id()` 的 App。
- **理由**：資料遷移階段可能尚無 User（或需指定 owner），nullable 讓遷移不阻塞；正式建立流程永遠由登入人員觸發、帶入 `auth()->id()`。
- **替代方案**：`user_id` 非空。缺點是遷移時若無 User 會失敗，需強制要求先有使用者。

### Decision 3: 用不透明 state + DB 查表承載上下文（而非 state 內編碼）
- **做法**：state 本身是 `bin2hex(random_bytes(32))` 的不透明 token；新增 `threads_oauth_states` 表存 `token`（sha256 hash）、`threads_app_id`、`threads_account_id`（nullable，重新授權時帶入）、`expires_at`。回呼時以 hash 查表取得 App 與目標帳號。
- **理由**：把「App 身分」「重新授權目標」放在 DB 欄位而非編碼進 state，避免 base64 解碼/竄改風險，且 state 無資訊洩漏。DB 存 hash 而非明文，降低 DB 外洩時被重放的風險。
- **替代方案**：state 用 `base64(json)` 編碼 app_id。缺點是可被解讀、需自行加簽，且多分頁仍需 DB 或 key 隔離。

### Decision 4: `ThreadsClient` OAuth 方法改收 `ThreadsApp`
- **做法**：
  - `buildAuthorizationUrl(ThreadsApp $app, string $state)`
  - `exchangeCodeForShortToken(ThreadsApp $app, string $code)`
  - `exchangeShortForLongToken(ThreadsApp $app, string $shortToken)`
  - `refreshLongLivedToken(string $token)` 不變（不需 client_secret）。
- **理由**：`client_id`/`client_secret` 改從 `$app` 讀取；`redirect_uri` 仍讀 `config('services.threads.redirect_uri')`（統一值）。
- **替代方案**：把 app 憑證當參數裸傳。缺點是簽名易錯、失去型別安全性。

### Decision 5: `redirect_uri` 留在 config，由 `APP_URL` 推導
- **做法**：`config/services.php` 的 `threads.redirect_uri` 改為 `rtrim((string) config('app.url'), '/').'/threads/oauth/callback'`，`.env` 移除 `THREADS_REDIRECT_URI`；`config/app.php` 的 `APP_URL` 已存在，不新增 env。
- **理由**：所有 App 共用同一回呼網址，無需逐 App 設定；由 `APP_URL` 推導可隨部署環境自動正確。
- **替代方案**：保留 `THREADS_REDIRECT_URI` env 並要求使用者手動填。缺點是多環境易漏填。使用者明確要求 `${APP_URL}/threads/oauth/callback` 推導方式。

### Decision 6: OAuth 路由帶 App 參數
- **做法**：`GET /threads/oauth/{app}/redirect`（route model binding `ThreadsApp`），`GET /threads/oauth/callback` 維持不變。
- **理由**：綁定一定從某個 App 發起，帶 App 參數明確；callback 不分 App，靠 state 查表。
- **替代方案**：`redirect?app_id=1` query param。缺點是不如 route binding 明確、型別安全。

### Decision 7: Filament 資源結構（採用選項 B，獨立資源）
- **做法**：
  - 新增 `ThreadsAppResource`（CRUD）：列表 toolbarl/row action「綁定帳號」導向 `route('threads.oauth.redirect', ['app' => $app])`。
  - `ThreadsAccountResource` 保留獨立資源：新增以 App 為維度的 `SelectFilter`；新增 row action「重新授權」導向同 App 的 redirect，並在 state 帶入該帳號 id。
- **理由**：使用者明確選擇選項 B（獨立資源 + 篩選），維持現有帳號列表頁習慣。
- **替代方案**：巢狀資源（App 內嵌帳號）。缺點是改動較大、與現有 `ThreadsAccountResource` 結構重疊。

### Decision 8: 資料遷移落地既有 `.env` 憑證
- **做法**：migration 在建立 `threads_apps` 後，讀取 `env('THREADS_CLIENT_ID')`/`env('THREADS_CLIENT_SECRET')`（舊值），若兩者皆非空，則建立一筆 App 關聯到第一個存在的 User（無 User 則 `user_id = null`），並將既有 `threads_accounts` 全部關聯到該 App。
- **理由**：平滑升級，避免既有綁定帳號失去歸屬。
- **替代方案**：要求使用者手動重建。缺點是資料遺失風險高。

## Risks / Trade-offs

- **[遷移讀不到舊 env 值]** → 若部署時先改 `.env` 再跑 migration，`env()` 讀不到舊憑證。遷移計畫要求「先跑 migration 再移除 `.env` 的 THREADS_CLIENT_ID/SECRET」，並在 tasks 中明確順序。
- **[user_id = null 的 App 不可見]** → 遷移時若無 User，該 App 對所有人不可見，導致既有帳號「隱形」。補救：遷移後手動指定 owner，或接受「首筆 App 需有 User 才建立」的約束（在 Open Questions 標記）。
- **[state 查表增加 DB 寫入]** → 每次綁定多一次寫入，可接受；需定期清理過期 state（可後續排程處理，非本次必要）。
- **[route binding 需 ThreadsApp 對目前 User 可見]** → `{app}` 解析後需在 controller 驗證 `$app->user_id === auth()->id()`，防止越權綁定他人 App。
- **[`client_secret` 加密依賴 APP_KEY]** → 若 APP_KEY 更換會無法解密，與既有 `access_token` 加密限制相同，屬已知既定風險。

## Migration Plan

1. 新增 migration：建立 `threads_apps`、`threads_oauth_states` 表，`threads_accounts` 加 `threads_app_id` 欄位。
2. 資料落地 migration：讀取舊 `env('THREADS_CLIENT_ID')`/`THREADS_CLIENT_SECRET` 建立首筆 App 並關聯既有帳號。
3. 部署順序：**先執行 `php artisan migrate`（此時 `.env` 仍含舊值）**，再移除 `.env` 的 `THREADS_CLIENT_ID`/`THREADS_CLIENT_SECRET`。
4. 回滾：`php artisan migrate:rollback` 可移除新表與欄位；資料落地屬不可逆但無損（僅是建立/關聯資料）。

## Open Questions

- 遷移時若不存在任何 User，首筆 App 的 `user_id` 要設 null（暫不可見）還是直接跳過建立？傾向設 null 並於 tasks 加一步「提示使用者指定 owner」，待確認。
- 過期 `threads_oauth_states` 的定期清理是否納入本次範圍？傾向延後（不影響功能正確性）。

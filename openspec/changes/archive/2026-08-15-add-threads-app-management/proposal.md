## Why

目前 Threads 的 OAuth 設定（`client_id`、`client_secret`、`redirect_uri`）寫死在 `.env`，整個系統只能綁定「單一 Meta App」下的 Threads 帳號。但實際使用情境是：一個登入人員需要管理多個 Meta App，每個 App 底下又各自綁定多個 Threads 測試人員帳號。這需要把 App 層級設定從 `.env` 移到資料庫，才能做到多 App、多帳號的彈性管理。

## What Changes

- 新增 `threads_apps` 資料表與 `ThreadsApp` Model，存放每個 Meta App 的 `client_id`、`client_secret`（加密儲存）與顯示名稱，並關聯到建立它的 `User`（一個登入人員管理多個 App）。
- 在 `threads_accounts` 表新增 `threads_app_id` 外鍵，讓每個 Threads 帳號歸屬於特定 App。
- OAuth 綁定流程改為「從 App 發起」：`state` 參數承載發起的 `threads_app_id`，並將 state 存入資料庫（帶過期時間）取代原本的單一 session key，解決多分頁競態與 CSRF 防護。
- `ThreadsClient` 的 OAuth 相關方法（`buildAuthorizationUrl`、`exchangeCodeForShortToken`、`exchangeShortForLongToken`）改為接收 `ThreadsApp`，從 App 讀取 `client_id` / `client_secret`，而非讀 `.env`。
- `redirect_uri` 保持統一，不進 DB；`.env` 改為 `THREADS_REDIRECT_URI="${APP_URL}/threads/oauth/callback"`，由 `APP_URL` 推導。
- Filament 新增「Threads App」管理資源（CRUD），並在 App 列表提供「綁定帳號」入口；既有「Threads 帳號」列表保留獨立資源，以 App 作為篩選條件，並補「重新授權」按鈕。
- 提供資料遷移：將 `.env` 現有 App 設定自動建立一筆 `threads_apps`，並把既有 `threads_accounts` 關聯到該 App。
- **BREAKING**：`.env` 移除 `THREADS_CLIENT_ID`、`THREADS_CLIENT_SECRET`（改存 DB），`THREADS_REDIRECT_URI` 改為以 `APP_URL` 推導。

## Capabilities

### New Capabilities

- `threads-app-management`: 多 Meta App 管理（CRUD）、App 層級 OAuth 綁定/重新授權、Threads 帳號歸屬 App、基於 DB 的 OAuth state 防護。

### Modified Capabilities

（無既有 capability 的 spec 級行為變更）

## Impact

- 資料庫：新增 `threads_apps` 表；`threads_accounts` 新增 `threads_app_id` 外鍵。
- 新增檔案：`ThreadsApp` Model、`ThreadsAppFactory`、`ThreadsAppResource`（Filament）、migration、可能的 `OAuthState` Model 或專用表。
- 修改檔案：`ThreadsClient`、`ThreadsOAuthController`、`ThreadsAccountResource`、`ThreadsAccountsTable`、`config/services.php`、`.env`、`.env.example`、`routes/web.php`。
- 既有 Job（`RefreshThreadsTokens`、`CollectThreadsReplies`、`PublishScheduledPost`）多數不需改動，因其已透過 `ThreadsAccount::access_token` 運作，與 App 層級無關。

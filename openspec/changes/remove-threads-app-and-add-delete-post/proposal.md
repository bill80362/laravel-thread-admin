## Why

目前 Threads App 憑證以資料庫表格（`threads_apps`）管理，支援多 App，但實際營運僅需單一 Meta App，多 App 管理徒增複雜度。此外，OAuth callback 綁定帳號時以 `auth()->id()` 判定使用者，且 `updateOrCreate` 僅以 `threads_user_id` 作為查詢條件，可能導致不同登入人員綁定同一 Threads 帳號時互相覆蓋。同時，系統缺少刪除已發佈 Threads 貼文的能力，營運人員無法下架貼文並追蹤刪除結果。

## What Changes

- **BREAKING** 移除 `ThreadsApp` 模型、資料表、Filament 管理介面與相關 Factory/Seeder，Threads API 憑證改由環境變數（`THREADS_CLIENT_ID`、`THREADS_CLIENT_SECRET`）提供，並於 `.env.example` 補上範本。
- **BREAKING** `ThreadsAccount` 移除 `threads_app_id` 外鍵與關聯，`OAuthState` 移除 `threads_app_id`。
- OAuth state 明確承載 `user_id`，callback 綁定時使用 state 解析出的 `user_id` 而非 `auth()->id()`，且 `updateOrCreate` 查詢條件加入 `user_id`，防止跨登入人員覆蓋綁定。
- 新增刪除貼文功能：呼叫 Threads API `DELETE /{threads-media-id}` 刪除已發佈貼文，成功才移除本地記錄，失敗則寫入錯誤訊息並保留記錄、狀態標記為可重試。
- **BREAKING** MCP 移除本地（Artisan）模式，僅保留 HTTP 模式。
- MCP 維持 Threads 帳號唯讀（僅 `list-accounts`），不提供新增／修改／刪除。
- 更新 `/admin/usage-guide` 使用說明與 `README.md`。

## Capabilities

### New Capabilities

- `post-deletion`: 刪除已發佈 Threads 貼文，含刪除狀態機、重試機制與錯誤記錄。

### Modified Capabilities

- `threads-app-management`: 移除多 App 管理與資料庫儲存，改為單一環境變數設定。
- `mcp-server`: 移除本地（Artisan）模式；明確 Threads 帳號僅可讀取。

## Impact

- **Models**: `ThreadsApp`（刪除）、`ThreadsAccount`（移除 `threads_app_id`）、`OAuthState`（移除 `threads_app_id`）、`User`（移除 `threadsApps` 關聯）、`Post`（新增刪除狀態）
- **Enums**: `PostStatus` 新增 `Deleting`、`DeleteFailed`
- **Services**: `ThreadsClient`（改由 config 讀取憑證、新增 `deleteMedia`）、`PostService`（新增刪除流程）、`ThreadsOAuthController`（改由 config 與 state 的 `user_id`）
- **Jobs**: 新增 `DeletePost` job
- **Migrations**: 移除 `threads_apps` 表、`threads_accounts.threads_app_id` 欄位
- **Filament**: 刪除 `ThreadsAppResource` 目錄、調整 `ThreadsAccountsTable`、`PostsTable`（新增刪除動作）
- **MCP**: `routes/ai.php` 移除 `Mcp::local`
- **Docs**: `.env.example`、`README.md`、`usage-guide` views
- **Tests**: 更新 `ThreadsAppResourceTest`、`ThreadsClientTest`、`OAuthStateTest` 等

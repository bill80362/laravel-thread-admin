## REMOVED Requirements

### Requirement: 登入人員可管理多個 Threads App
**Reason**: 簡化架構，實際營運僅需單一 Meta App，多 App 管理徒增複雜度。
**Migration**: Threads API 憑證（`client_id`、`client_secret`）改由 `.env` 環境變數 `THREADS_CLIENT_ID`、`THREADS_CLIENT_SECRET` 提供。現有 `threads_apps` 表格資料需手動遷移至 `.env`，`threads_accounts.threads_app_id` 欄位移除。

### Requirement: Threads 帳號歸屬於特定 App
**Reason**: 隨 ThreadsApp 移除，帳號不再需要 `threads_app_id` 關聯。
**Migration**: `threads_accounts` 表格移除 `threads_app_id` 外鍵欄位。

### Requirement: 從 App 發起 OAuth 綁定
**Reason**: OAuth 綁定不再需要選擇 App，統一使用環境變數中的憑證。
**Migration**: OAuth redirect 路由不再需要 `{app}` 路徑參數，`ThreadsClient` 改從 `config('services.threads')` 讀取憑證。

### Requirement: OAuth state 承載 App 身分並儲存於資料庫
**Reason**: OAuth state 不再需要承載 App 身分，僅需承載 `user_id` 與可選的 `account_id`。
**Migration**: `OAuthState` 移除 `threads_app_id` 欄位與關聯。

### Requirement: 重新授權既有帳號
**Reason**: 重新授權流程不再需要 App 上下文，但功能本身保留。
**Migration**: 重新授權路由不再需要 `{app}` 路徑參數。

### Requirement: 統一的回呼網址
**Reason**: 此需求不再適用，因為只剩單一 App，無需區分多個 App 的回呼。
**Migration**: 無需特別處理，回呼網址保持不變。

## MODIFIED Requirements

### Requirement: 綁定帳號記錄所屬使用者
每個 Threads 帳號 SHALL 歸屬於一位使用者（`user_id`），綁定流程必須記錄帳號所屬的使用者，且 `user_id` 由 OAuth state 解析取得而非依賴當前 session。

#### Scenario: 綁定帳號記錄所屬使用者
- **WHEN** 使用者完成 OAuth 綁定
- **THEN** 新建立或更新的 Threads 帳號記錄其 `user_id` 為 OAuth state 中儲存的使用者 ID
- **AND** `updateOrCreate` 查詢條件 SHALL 同時包含 `threads_user_id` 與 `user_id`，防止不同使用者綁定同一 Threads 帳號時互相覆蓋

#### Scenario: OAuth state 承載使用者身分
- **WHEN** 系統建立 OAuth state
- **THEN** state SHALL 儲存當前登入使用者的 `user_id`
- **AND** callback 解析 state 時 SHALL 回傳該 `user_id`
- **AND** 綁定流程 SHALL 使用 state 中的 `user_id` 而非 `auth()->id()`

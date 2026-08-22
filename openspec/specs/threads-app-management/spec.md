## Purpose

讓登入人員以環境變數（`THREADS_CLIENT_ID`、`THREADS_CLIENT_SECRET`）設定單一 Meta App 憑證，並在其下綁定多個 Threads 測試人員帳號，每個帳號獨立持有 access token 並可分別重新授權。

## Requirements

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

### Requirement: Webhook 回呼網址
系統 SHALL 提供 Webhook 回呼網址，供 Meta Threads App 設定即時事件通知。

#### Scenario: 提供 Webhook 回呼網址
- **WHEN** 管理者需要設定 Meta Threads App 的 Webhook 回呼網址
- **THEN** 系統 SHALL 提供 `${APP_URL}/threads/webhook` 作為 Webhook 回呼網址
- **AND** 該網址 SHALL 由 `APP_URL` 推導，無需手動設定

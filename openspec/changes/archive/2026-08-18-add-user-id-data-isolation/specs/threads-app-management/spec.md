## MODIFIED Requirements

### Requirement: Threads 帳號歸屬於特定 App
每個 Threads 帳號 SHALL 歸屬於一個 Threads App 與一位使用者（`user_id`），綁定流程必須記錄帳號所屬的 App 與使用者。

#### Scenario: 綁定帳號記錄所屬 App 與使用者
- **WHEN** 使用者在某個 App 上完成 OAuth 綁定
- **THEN** 新建立或更新的 Threads 帳號記錄其 `threads_app_id` 指向該 App
- **AND** 記錄其 `user_id` 為當前登入使用者

### Requirement: OAuth state 承載 App 身分並儲存於資料庫
OAuth 流程 SHALL 使用儲存於資料庫、具過期時間的 `state` 值來承載發起的 App 身分與使用者身分，並在回呼時驗證其有效性。

#### Scenario: state 於回呼時可解析出 App 與使用者
- **WHEN** Threads 回呼攜帶 `code` 與 `state`
- **THEN** 系統解析 `state` 得到發起的 App 身分與使用者身分
- **AND** 使用該 App 的憑證交換 token
- **AND** 驗證 `state` 的 `user_id` 與當前登入使用者一致

#### Scenario: state 失效或不存在
- **WHEN** 回呼的 `state` 不存在、已過期或格式不合法
- **THEN** 系統拒絕綁定並導向錯誤頁面，不建立或更新任何帳號

#### Scenario: 多個綁定分頁互不干擾
- **WHEN** 登入人員同時開啟多個綁定分頁（不同 App）
- **THEN** 各分頁的 `state` 獨立，回呼能正確對應各自的 App

## Purpose

讓登入人員可以管理多個 Meta App，並在每個 App 底下各自綁定多個 Threads 測試人員帳號，每個帳號獨立持有 access token 並可分別重新授權。

## ADDED Requirements

### Requirement: 登入人員可管理多個 Threads App
系統 SHALL 讓每位登入人員建立並管理多個 Threads App，每個 App 儲存自己的 `client_id` 與 `client_secret`，並只對建立它的登入人員可見。

#### Scenario: 建立新的 Threads App
- **WHEN** 登入人員建立一個 Threads App 並填入 `client_id`、`client_secret` 與顯示名稱
- **THEN** 系統儲存該 App，且 `client_secret` 以加密形式儲存
- **AND** 該 App 僅對建立者可見

#### Scenario: 檢視自己的 App 列表
- **WHEN** 登入人員開啟 Threads App 管理頁面
- **THEN** 系統僅顯示該登入人員建立的 App

### Requirement: Threads 帳號歸屬於特定 App
每個 Threads 帳號 SHALL 歸屬於一個 Threads App，綁定流程必須記錄帳號所屬的 App。

#### Scenario: 綁定帳號記錄所屬 App
- **WHEN** 使用者在某個 App 上完成 OAuth 綁定
- **THEN** 新建立或更新的 Threads 帳號記錄其 `threads_app_id` 指向該 App

### Requirement: 從 App 發起 OAuth 綁定
OAuth 綁定流程 SHALL 由特定 App 發起，且授權網址與 token 交換必須使用該 App 的 `client_id` 與 `client_secret`。

#### Scenario: 使用對應 App 的憑證發起授權
- **WHEN** 使用者在某個 App 點擊綁定帳號
- **THEN** 授權網址使用該 App 的 `client_id`
- **AND** token 交換使用該 App 的 `client_id` 與 `client_secret`

### Requirement: OAuth state 承載 App 身分並儲存於資料庫
OAuth 流程 SHALL 使用儲存於資料庫、具過期時間的 `state` 值來承載發起的 App 身分，並在回呼時驗證其有效性。

#### Scenario: state 於回呼時可解析出 App
- **WHEN** Threads 回呼攜帶 `code` 與 `state`
- **THEN** 系統解析 `state` 得到發起的 App 身分
- **AND** 使用該 App 的憑證交換 token

#### Scenario: state 失效或不存在
- **WHEN** 回呼的 `state` 不存在、已過期或格式不合法
- **THEN** 系統拒絕綁定並導向錯誤頁面，不建立或更新任何帳號

#### Scenario: 多個綁定分頁互不干擾
- **WHEN** 登入人員同時開啟多個綁定分頁（不同 App）
- **THEN** 各分頁的 `state` 獨立，回呼能正確對應各自的 App

### Requirement: 重新授權既有帳號
系統 SHALL 允許對已綁定的 Threads 帳號（包含 `needs_reauth` 狀態）重新授權，而不需先解除綁定。

#### Scenario: 重新授權 token 失效的帳號
- **WHEN** 使用者在狀態為「需重新授權」的帳號上觸發重新授權並完成 OAuth
- **THEN** 系統更新該帳號的 access token 與到期時間，並將狀態回復為已綁定

### Requirement: 統一的回呼網址
所有 App SHALL 使用同一個回呼網址（由 `APP_URL` 推導），App 身分的分辨不依賴回呼網址。

#### Scenario: 多個 App 共用回呼網址
- **WHEN** 多個 App 各自完成 OAuth 綁定
- **THEN** 系統透過 `state` 分辨發起的 App，而非回呼網址

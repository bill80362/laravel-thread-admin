## Purpose

確保所有業務資料（Threads 帳號、貼文、回覆、OAuth 狀態）以 `user_id` 進行使用者層級隔離，每位使用者僅能存取與操作自己的資料。

## Requirements

### Requirement: Threads 帳號歸屬於使用者
每個 Threads 帳號 SHALL 直接歸屬於一位使用者（`user_id`），寫入時取自 `auth()->id()`。

#### Scenario: 綁定帳號時記錄使用者
- **WHEN** 使用者在某個 App 上完成 OAuth 綁定
- **THEN** 新建立或更新的 Threads 帳號 SHALL 記錄 `user_id` 為當前登入使用者

#### Scenario: 後台僅顯示自己的帳號
- **WHEN** 登入人員開啟 Threads 帳號管理頁面
- **THEN** 系統 SHALL 僅顯示 `user_id` 等於當前登入使用者的帳號

### Requirement: 貼文歸屬於使用者
每筆貼文 SHALL 直接歸屬於一位使用者（`user_id`），寫入時取自 `auth()->id()`。

#### Scenario: 建立貼文時記錄使用者
- **WHEN** 登入人員或 MCP 工具建立一筆貼文
- **THEN** 貼文 SHALL 記錄 `user_id` 為當前操作使用者

#### Scenario: 後台僅顯示自己的貼文
- **WHEN** 登入人員開啟貼文管理頁面
- **THEN** 系統 SHALL 僅顯示 `user_id` 等於當前登入使用者的貼文

### Requirement: 回覆歸屬於使用者
每筆回覆 SHALL 直接歸屬於一位使用者（`user_id`），寫入時取自 `auth()->id()`。

#### Scenario: 建立回覆時記錄使用者
- **WHEN** 登入人員或 MCP 工具建立一筆回覆
- **THEN** 回覆 SHALL 記錄 `user_id` 為當前操作使用者

#### Scenario: 輪詢抓回回覆時記錄帳號所屬使用者
- **WHEN** 排程輪詢從 Threads 抓回一筆回覆
- **THEN** 回覆 SHALL 記錄 `user_id` 為其所屬 Threads 帳號的 `user_id`

#### Scenario: 後台僅顯示自己的回覆
- **WHEN** 登入人員開啟回覆管理頁面
- **THEN** 系統 SHALL 僅顯示 `user_id` 等於當前登入使用者的回覆

### Requirement: OAuth 狀態歸屬於使用者
每筆 OAuth 狀態 SHALL 直接歸屬於一位使用者（`user_id`），寫入時取自 `auth()->id()`。

#### Scenario: 發起 OAuth 綁定時記錄使用者
- **WHEN** 登入人員發起 OAuth 綁定流程
- **THEN** 系統 SHALL 建立一筆 OAuth 狀態並記錄 `user_id` 為當前登入使用者

#### Scenario: 回呼時驗證使用者
- **WHEN** Threads 回呼攜帶 `state`
- **THEN** 系統 SHALL 驗證該 OAuth 狀態的 `user_id` 與當前登入使用者一致
- **AND** 若不一致則拒絕綁定

### Requirement: MCP 工具以 OAuth token 所屬使用者進行資料隔離
所有 MCP 工具的查詢與寫入操作 SHALL 以 OAuth token 所屬使用者（`auth()->id()`）進行 scope，僅回傳或操作該使用者的資料。

#### Scenario: MCP 列出帳號僅回傳自己的帳號
- **WHEN** AI agent 透過 MCP 呼叫 `list-accounts`
- **THEN** 系統 SHALL 僅回傳 OAuth token 所屬使用者的帳號

#### Scenario: MCP 查詢貼文僅回傳自己的貼文
- **WHEN** AI agent 透過 MCP 呼叫 `list-posts` 或 `get-post`
- **THEN** 系統 SHALL 僅回傳 OAuth token 所屬使用者的貼文

#### Scenario: MCP 查詢回覆僅回傳自己的回覆
- **WHEN** AI agent 透過 MCP 呼叫 `list-replies`
- **THEN** 系統 SHALL 僅回傳 OAuth token 所屬使用者的回覆

#### Scenario: MCP 建立貼文時驗證帳號歸屬
- **WHEN** AI agent 透過 MCP 呼叫 `create-post` 指定 `threads_account_id`
- **THEN** 系統 SHALL 驗證該帳號的 `user_id` 與 OAuth token 所屬使用者一致
- **AND** 若不一致則拒絕建立

#### Scenario: MCP 建立回覆時驗證帳號歸屬
- **WHEN** AI agent 透過 MCP 呼叫 `create-reply` 指定 `threads_account_id`
- **THEN** 系統 SHALL 驗證該帳號的 `user_id` 與 OAuth token 所屬使用者一致
- **AND** 若不一致則拒絕建立

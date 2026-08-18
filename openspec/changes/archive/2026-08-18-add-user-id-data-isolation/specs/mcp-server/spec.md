## MODIFIED Requirements

### Requirement: 列出可用帳號
系統 SHALL 提供 `list-accounts` 工具，回傳 OAuth token 所屬使用者下可供發文與回覆的已綁定 Threads 帳號清單。

#### Scenario: 列出帳號
- **WHEN** AI agent 呼叫 `list-accounts`
- **THEN** 系統 SHALL 回傳 OAuth token 所屬使用者的已綁定帳號清單，包含帳號 ID、使用者名稱、顯示名稱與狀態

#### Scenario: 依狀態篩選帳號
- **WHEN** AI agent 呼叫 `list-accounts` 並指定狀態篩選
- **THEN** 系統 SHALL 僅回傳 OAuth token 所屬使用者中符合該狀態的帳號

### Requirement: 建立排程貼文
系統 SHALL 提供 `create-post` 工具，依指定帳號、內容與排程時間建立一筆排程貼文，並驗證指定帳號歸屬於 OAuth token 所屬使用者。

#### Scenario: 建立排程貼文
- **WHEN** AI agent 提供帳號、貼文內容與排程時間呼叫 `create-post`
- **THEN** 系統 SHALL 驗證該帳號歸屬於 OAuth token 所屬使用者
- **AND** 系統 SHALL 建立一筆狀態為「排程中」的貼文，`user_id` 設為 OAuth token 所屬使用者
- **AND** 回傳新建貼文的資訊

#### Scenario: 缺少必填欄位
- **WHEN** AI agent 呼叫 `create-post` 但缺少帳號、內容或排程時間
- **THEN** 系統 SHALL 回傳驗證錯誤，且不建立貼文

#### Scenario: 指定不屬於自己的帳號
- **WHEN** AI agent 呼叫 `create-post` 指定不屬於 OAuth token 所屬使用者的 `threads_account_id`
- **THEN** 系統 SHALL 回傳驗證錯誤，且不建立貼文

### Requirement: 查詢貼文清單
系統 SHALL 提供 `list-posts` 工具，回傳 OAuth token 所屬使用者的貼文清單，並支援依帳號、狀態篩選。

#### Scenario: 列出貼文
- **WHEN** AI agent 呼叫 `list-posts`
- **THEN** 系統 SHALL 回傳 OAuth token 所屬使用者的貼文清單，包含內容、狀態、排程與發佈時間

#### Scenario: 依帳號與狀態篩選
- **WHEN** AI agent 呼叫 `list-posts` 並指定帳號或狀態
- **THEN** 系統 SHALL 僅回傳 OAuth token 所屬使用者中符合條件的貼文

### Requirement: 查詢單一貼文
系統 SHALL 提供 `get-post` 工具，依貼文 ID 回傳單一貼文的詳細資訊，且僅限 OAuth token 所屬使用者的貼文。

#### Scenario: 查詢存在的貼文
- **WHEN** AI agent 提供有效貼文 ID 呼叫 `get-post`
- **THEN** 系統 SHALL 回傳該貼文的完整資訊（僅限 OAuth token 所屬使用者）

#### Scenario: 查詢不存在的貼文
- **WHEN** AI agent 提供不存在的貼文 ID 呼叫 `get-post`，或該貼文不屬於 OAuth token 所屬使用者
- **THEN** 系統 SHALL 回傳錯誤，表示貼文不存在

### Requirement: 查詢回覆清單
系統 SHALL 提供 `list-replies` 工具，回傳 OAuth token 所屬使用者的回覆清單，並支援依帳號、貼文、狀態篩選。

#### Scenario: 列出回覆
- **WHEN** AI agent 呼叫 `list-replies`
- **THEN** 系統 SHALL 回傳 OAuth token 所屬使用者的回覆清單，包含留言者、內容、狀態與時間

#### Scenario: 依帳號、貼文與狀態篩選
- **WHEN** AI agent 呼叫 `list-replies` 並指定帳號、貼文或狀態
- **THEN** 系統 SHALL 僅回傳 OAuth token 所屬使用者中符合條件的回覆

### Requirement: 建立貼文回覆
系統 SHALL 提供 `create-reply` 工具，依指定帳號、目標貼文與回覆內容建立一筆貼文回覆並發佈，並驗證指定帳號歸屬於 OAuth token 所屬使用者。

#### Scenario: 建立貼文回覆
- **WHEN** AI agent 提供來源帳號、目標貼文與回覆內容呼叫 `create-reply`
- **THEN** 系統 SHALL 驗證該帳號歸屬於 OAuth token 所屬使用者
- **AND** 系統 SHALL 建立一筆貼文回覆並觸發發佈，`user_id` 設為 OAuth token 所屬使用者
- **AND** `source` 自動設為 `manual`

#### Scenario: 缺少必填欄位
- **WHEN** AI agent 呼叫 `create-reply` 但缺少來源帳號、目標貼文或回覆內容
- **THEN** 系統 SHALL 回傳驗證錯誤
- **AND** 系統 SHALL 不建立回覆

#### Scenario: 指定不屬於自己的帳號
- **WHEN** AI agent 呼叫 `create-reply` 指定不屬於 OAuth token 所屬使用者的 `threads_account_id`
- **THEN** 系統 SHALL 回傳驗證錯誤，且不建立回覆

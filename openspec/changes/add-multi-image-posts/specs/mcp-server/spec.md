## MODIFIED Requirements

### Requirement: 建立排程貼文
系統 SHALL 提供 `create-post` 工具，依指定帳號、內容（或圖片 URL 陣列）與排程時間建立一筆排程貼文，並驗證指定帳號歸屬於 OAuth token 所屬使用者。

#### Scenario: 建立排程貼文
- **WHEN** AI agent 提供帳號、貼文內容與排程時間呼叫 `create-post`
- **THEN** 系統 SHALL 驗證該帳號歸屬於 OAuth token 所屬使用者
- **AND** 系統 SHALL 建立一筆狀態為「排程中」的貼文，`user_id` 設為 OAuth token 所屬使用者
- **AND** 回傳新建貼文的資訊

#### Scenario: 建立含多張圖片的排程貼文
- **WHEN** AI agent 提供帳號、貼文內容、`image_urls` 陣列（2-10 個公開 URL）與排程時間呼叫 `create-post`
- **THEN** 系統 SHALL 建立貼文並儲存所有圖片記錄，依陣列順序設定排序
- **AND** 回傳新建貼文的資訊（含圖片清單）

#### Scenario: 缺少必填欄位
- **WHEN** AI agent 呼叫 `create-post` 但缺少帳號、內容或排程時間
- **THEN** 系統 SHALL 回傳驗證錯誤，且不建立貼文

#### Scenario: 圖片 URL 數量超過上限
- **WHEN** AI agent 呼叫 `create-post` 並提供超過 10 個 `image_urls`
- **THEN** 系統 SHALL 回傳驗證錯誤，提示圖片數量上限為 10 張

#### Scenario: 指定不屬於自己的帳號
- **WHEN** AI agent 呼叫 `create-post` 指定不屬於 OAuth token 所屬使用者的 `threads_account_id`
- **THEN** 系統 SHALL 回傳驗證錯誤，且不建立貼文

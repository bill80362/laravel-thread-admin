## REMOVED Requirements

### Requirement: 建立手動回覆記錄
**Reason**: `create-reply` 工具的語義從「建立一筆手動回覆記錄（僅寫入本機、不發佈）」翻轉為「建立貼文回覆並實際發佈」，參數與行為隨之變更。
**Migration**: 使用下方新增的「建立貼文回覆」，移除 `author_username` 參數並將 `post_id` 改為必填。

## ADDED Requirements

### Requirement: 建立貼文回覆
系統 SHALL 提供 `create-reply` 工具，依指定帳號、目標貼文與回覆內容建立一筆貼文回覆並發佈，其行為與後台介面「新增貼文回覆」一致。

#### Scenario: 建立貼文回覆
- **WHEN** AI agent 提供來源帳號、目標貼文與回覆內容呼叫 `create-reply`
- **THEN** 系統 SHALL 建立一筆貼文回覆並觸發發佈
- **AND** `source` 自動設為 `manual`

#### Scenario: 缺少必填欄位
- **WHEN** AI agent 呼叫 `create-reply` 但缺少來源帳號、目標貼文或回覆內容
- **THEN** 系統 SHALL 回傳驗證錯誤
- **AND** 系統 SHALL 不建立回覆

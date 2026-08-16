## REMOVED Requirements

### Requirement: 手動新增回覆表單
**Reason**: 語義從「建立一筆手動回覆記錄（僅寫入本機、不發佈）」翻轉為「新增貼文回覆並實際發佈」，欄位與行為隨之變更。
**Migration**: 使用下方新增的「新增貼文回覆表單」，移除「留言者」欄位並將「所屬貼文」改為必填。

## ADDED Requirements

### Requirement: 新增貼文回覆表單
系統 SHALL 提供「新增貼文回覆」表單，包含來源帳號、所屬貼文（必填）與回覆內容等欄位，建立後觸發發佈。

#### Scenario: 成功建立貼文回覆
- **WHEN** 管理者填寫所有必填欄位（來源帳號、所屬貼文、回覆內容）
- **AND** 點擊建立按鈕
- **THEN** 系統 SHALL 建立一筆貼文回覆記錄並觸發發佈
- **AND** `source` 自動設為 `manual`

#### Scenario: 必填欄位驗證失敗
- **WHEN** 管理者未填寫來源帳號、所屬貼文或回覆內容
- **AND** 點擊建立按鈕
- **THEN** 系統 SHALL 顯示對應的驗證錯誤訊息
- **AND** 系統 SHALL 不建立回覆記錄

## MODIFIED Requirements

### Requirement: 新增頁面路由
系統 SHALL 提供獨立的回覆新增頁面路由。

#### Scenario: 導航至新增頁面
- **WHEN** 管理者在回覆列表頁點擊「新增貼文回覆」按鈕
- **THEN** 系統 SHALL 導航至 `/admin/replies/create` 頁面
- **AND** 顯示完整的貼文回覆新增表單

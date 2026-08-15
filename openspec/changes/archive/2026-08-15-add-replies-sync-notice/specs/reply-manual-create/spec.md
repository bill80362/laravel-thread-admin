## MODIFIED Requirements

### Requirement: 手動新增回覆表單
系統 SHALL 提供完整的回覆新增表單，包含來源帳號、所屬貼文（可選）、留言者名稱、留言內容等欄位。

#### Scenario: 成功建立回覆
- **WHEN** 管理者填寫所有必填欄位（來源帳號、留言者、留言內容）
- **AND** 點擊建立按鈕
- **THEN** 系統 SHALL 建立一筆新的回覆記錄
- **AND** `source` 自動設為 `manual`
- **AND** `status` 自動設為 `new`
- **AND** 頁面重新導向至回覆列表

#### Scenario: 必填欄位驗證失敗
- **WHEN** 管理者未填寫來源帳號、留言者或留言內容
- **AND** 點擊建立按鈕
- **THEN** 系統 SHALL 顯示對應的驗證錯誤訊息
- **AND** 不建立回覆記錄

#### Scenario: 可選欄位留空
- **WHEN** 管理者未選擇所屬貼文
- **AND** 點擊建立按鈕
- **THEN** 系統 SHALL 建立回覆記錄，`post_id` 為 null

## ADDED Requirements

### Requirement: 回覆資源複數標籤
系統 SHALL 使用「回覆」作為回覆資源的複數標籤，而非自動產生的「回覆s」。

#### Scenario: 回覆列表頁標題顯示
- **WHEN** 管理者進入回覆列表頁
- **THEN** 頁面標題 SHALL 顯示「回覆」而非「回覆s」

### Requirement: 導覽選單排序
系統 SHALL 依以下順序排列左側導覽選單中的資源項目：Dashboard → APP → 帳號 → 發文 → 回覆。

#### Scenario: 左側選單顯示順序
- **WHEN** 管理者登入後台
- **THEN** 左側導覽選單 SHALL 依序顯示 Dashboard、APP、帳號、發文、回覆

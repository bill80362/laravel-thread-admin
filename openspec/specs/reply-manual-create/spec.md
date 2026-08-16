# reply-manual-create Specification

## Purpose
定義管理者手動新增回覆記錄的功能，補齊回覆面板目前缺少的新增表單與建立頁面。

## Requirements

### Requirement: ReplySource 新增 Manual 來源
系統 SHALL 在 `ReplySource` enum 中提供 `Manual` 選項，用於標記手動建立的回覆。

#### Scenario: 手動建立的回覆標記為 manual
- **WHEN** 透過新增頁面建立回覆
- **THEN** 回覆的 `source` 欄位 SHALL 為 `manual`

### Requirement: 新增頁面路由
系統 SHALL 提供獨立的回覆新增頁面路由。

#### Scenario: 導航至新增頁面
- **WHEN** 管理者在回覆列表頁點擊「新增貼文回覆」按鈕
- **THEN** 系統 SHALL 導航至 `/admin/replies/create` 頁面
- **AND** 顯示完整的貼文回覆新增表單

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

## MODIFIED Requirements

### Requirement: 回覆歸屬於使用者
每筆回覆 SHALL 直接歸屬於一位使用者（`user_id`）。手動建立的回覆寫入時取自 `auth()->id()`；輪詢（Polling）或 Webhook 來源抓回的回覆 SHALL 取自其所屬 Threads 帳號的 `user_id`，確保所有來源的回覆皆可被後台與 MCP 正確隔離與顯示。

#### Scenario: 建立回覆時記錄使用者
- **WHEN** 登入人員或 MCP 工具建立一筆回覆
- **THEN** 回覆 SHALL 記錄 `user_id` 為當前操作使用者

#### Scenario: 輪詢抓回回覆時記錄帳號所屬使用者
- **WHEN** 排程輪詢從 Threads 抓回一筆回覆
- **THEN** 回覆 SHALL 記錄 `user_id` 為其所屬 Threads 帳號的 `user_id`

#### Scenario: 後台僅顯示自己的回覆
- **WHEN** 登入人員開啟回覆管理頁面
- **THEN** 系統 SHALL 僅顯示 `user_id` 等於當前登入使用者的回覆

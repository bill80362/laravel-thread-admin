## Purpose

讓 MCP 客戶端在查詢貼文清單時能一併得知每篇貼文的回覆總數與未讀回覆數；查詢回覆清單時能收到 `read_at` 欄位，並可選擇在查詢後將指定貼文的回覆標記為已讀。

## ADDED Requirements

### Requirement: ListPostsTool 回傳回覆數量
系統 SHALL 在 MCP ListPostsTool 的每筆貼文回傳資料中附加 `reply_unread_count` 與 `reply_total_count` 欄位。

#### Scenario: ListPostsTool 回傳包含回覆統計
- **WHEN** MCP 客戶端呼叫 `list_posts` 工具
- **THEN** 每筆貼文回傳資料 SHALL 包含 `reply_unread_count`（該貼文未讀回覆數）與 `reply_total_count`（該貼文回覆總數）

### Requirement: ListRepliesTool 回傳 read_at 欄位
系統 SHALL 在 MCP ListRepliesTool 的回傳資料中新增 `read_at` 欄位，讓客戶端知道哪些回覆尚未讀取。

#### Scenario: ListRepliesTool 回傳含 read_at
- **WHEN** MCP 客戶端呼叫 `list_replies` 工具
- **THEN** 每筆回覆回傳資料 SHALL 包含 `read_at`（datetime 或 null）
- **AND** `read_at` 為 null 表示該回覆尚未讀取

### Requirement: ListRepliesTool 支援查詢後標記已讀
系統 SHALL 在 MCP ListRepliesTool 提供 `mark_as_read` 參數，當設為 true 時，查詢完成後將該使用者所有未讀回覆標記為已讀。

#### Scenario: mark_as_read=true 時查詢後標記已讀
- **WHEN** MCP 客戶端呼叫 `list_replies` 工具時帶入 `mark_as_read: true`
- **THEN** 查詢完成後 SHALL 將該使用者所有 `read_at IS NULL` 的回覆標記為已讀
- **AND** `read_at` 欄位 SHALL 更新為查詢時間

#### Scenario: mark_as_read=false 或未提供時不標記
- **WHEN** MCP 客戶端呼叫 `list_replies` 工具時未帶入 `mark_as_read` 或設為 `false`
- **THEN** 系統 SHALL 正常回傳結果但 SHALL 不修改任何回覆的 `read_at` 欄位

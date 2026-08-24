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

## MODIFIED Requirements

### Requirement: 回覆具備已讀狀態
系統 SHALL 為每筆回覆記錄已讀狀態，未讀回覆與已讀回覆需可區分。

#### Scenario: 新抓取的回覆為未讀
- **WHEN** 系統透過輪詢抓取到一筆新回覆
- **THEN** 該回覆 SHALL 標記為未讀

#### Scenario: 既有回覆標記為已讀
- **WHEN** 系統升級後處理既有回覆資料
- **THEN** 既有回覆 SHALL 標記為已讀

### Requirement: 計算未讀回覆數
系統 SHALL 能計算每筆貼文的未讀回覆數量，以決定是否顯示「有新回覆」警示。

#### Scenario: 查詢未讀回覆數
- **WHEN** 系統判斷貼文是否顯示「有新回覆」警示
- **THEN** 系統 SHALL 依該貼文的未讀回覆數量決定
- **AND** 未讀回覆數大於零時 SHALL 顯示警示

### Requirement: 計算全域未讀回覆總數（ADDED）
系統 SHALL 能計算該使用者的全域未讀回覆總數，以決定側邊欄 badge 顯示。

#### Scenario: 查詢全域未讀回覆總數
- **WHEN** 系統計算側邊欄 badge 顯示的未讀數
- **THEN** 系統 SHALL 計算該使用者 `read_at IS NULL` 的所有回覆筆數
- **AND** 該數值 SHALL 作為 badge 的分子顯示

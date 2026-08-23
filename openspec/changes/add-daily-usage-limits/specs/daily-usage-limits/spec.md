## Purpose

記錄與強制執行每位使用者的每日發文與回覆上限，確保用量不超過管理員設定的配額，並提供用量查詢與軟性警告。

## ADDED Requirements

### Requirement: 系統記錄每次發送成功

系統 SHALL 在每次貼文或回覆成功發送至 Threads 後，寫入一筆 `activity_logs` 記錄。

- 記錄包含：使用者 ID、Threads 帳號 ID、類型（post/reply）、關聯 ID、Threads media ID、發文內容（反標準化）、發送時間
- 刪除貼文後，該筆記錄不得被刪除，仍計入當日配額
- 記錄不建立資料庫外鍵約束

#### Scenario: 發文成功後寫入 log

- **WHEN** 排程貼文成功發佈至 Threads
- **THEN** `activity_logs` 表新增一筆 `type = 'post'` 的記錄，包含 `user_id`、`threads_account_id`、`reference_id`、`threads_media_id`、`text`、`created_at`

#### Scenario: 回覆成功後寫入 log

- **WHEN** 回覆成功發佈至 Threads
- **THEN** `activity_logs` 表新增一筆 `type = 'reply'` 的記錄

#### Scenario: 刪除貼文不影響 log

- **WHEN** 貼文從 Threads 刪除後，本地 Post 記錄被硬刪除
- **THEN** 對應的 `activity_logs` 記錄仍存在，`reference_id` 可能變為孤兒，但 `text` 欄位保留發文內容

### Requirement: 發送前檢查每日上限

系統 SHALL 在實際發送貼文或回覆前（建立 media container 前），檢查該使用者當日的 `activity_logs` 數量是否已達上限。

- 貼文檢查 `user.max_daily_posts`
- 回覆檢查 `user.max_daily_replies`
- 超額時將該筆 Post/Reply 標記為 Failed，error_message 設為「已達每日發文上限」或「已達每日回覆上限」
- 不阻擋建立排程，僅在發送時檢查

#### Scenario: 超額時標記失敗

- **WHEN** 使用者今日已發送 10 篇貼文（`max_daily_posts = 10`），又有新的排程貼文到達發送時間
- **THEN** 該貼文被標記為 `Failed`，`error_message` 設為「已達每日發文上限」，不呼叫 Threads API

#### Scenario: 未超額時正常發送

- **WHEN** 使用者今日已發送 5 篇貼文（`max_daily_posts = 10`），排程貼文到達發送時間
- **THEN** 正常建立 container 並發送

### Requirement: MCP 工具回傳軟性警告

MCP 工具 `CreatePostTool` 和 `CreateReplyTool` SHALL 在建立成功時，於回傳資料中附加 `warnings` 陣列，提示當前用量狀況。

- 貼文：顯示今日已發送數量、上限、以及今日排程中將發送的數量
- 回覆：顯示今日已回覆數量與上限
- 不阻擋建立，僅提供資訊

#### Scenario: MCP 建立貼文回傳警告

- **WHEN** 使用者透過 MCP 建立排程貼文，今日已發 8 篇（上限 10），尚有 3 篇排程將於今日發送
- **THEN** 回傳包含 `"warnings": ["今日已發文 8 篇（上限 10），尚有 3 篇排程將於今日發送"]`

#### Scenario: MCP 建立回覆回傳警告

- **WHEN** 使用者透過 MCP 建立回覆，今日已回覆 45 則（上限 50）
- **THEN** 回傳包含 `"warnings": ["今日已回覆 45 則（上限 50）"]`

### Requirement: User 端顯示用量提示條

User 面板的貼文列表頁頂部 SHALL 顯示今日發文與回覆的用量提示條。

- 用量條包含：已發送數量、上限、進度條視覺化
- 發文用量額外顯示「排程中今日將發送」的數量
- 用量計算以 `activity_logs` 表為準

#### Scenario: 用量條顯示正確數據

- **WHEN** 使用者進入貼文列表頁
- **THEN** 頂部顯示今日發文用量條（如 5/10）與今日回覆用量條（如 45/50）

### Requirement: Admin 可查詢用量明細

Admin 面板的 User 列表頁中，「今日發文」與「今日回覆」欄位 SHALL 可點擊，點擊後右側滑出抽屜顯示明細。

- 明細列出該使用者今日的每一筆發送記錄
- 每筆記錄顯示：發送時間、Threads 帳號、發文內容（若有）、關聯 ID
- 即使 Post 已被刪除，仍顯示發送時間與帳號

#### Scenario: Admin 點擊查看明細

- **WHEN** Admin 在 User 列表點擊某使用者的「今日發文 8/10」
- **THEN** 右側抽屜顯示該使用者今日的 8 筆發送明細

### Requirement: User 可查詢自己的用量明細

User 面板 SHALL 提供「發送紀錄」導航頁面，顯示自己的所有發送記錄。

- 列表顯示：發送時間、類型（貼文/回覆）、Threads 帳號、內容
- 支援依類型篩選
- 即使關聯的 Post/Reply 已被刪除，仍顯示記錄

#### Scenario: User 查看發送紀錄

- **WHEN** User 進入「發送紀錄」頁面
- **THEN** 顯示該使用者的所有 `activity_logs` 記錄，依時間倒序排列

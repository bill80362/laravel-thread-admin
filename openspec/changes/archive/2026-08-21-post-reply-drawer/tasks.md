## 1. 資料庫與 Model

- [x] 1.1 建立 migration：`replies` 表新增 `read_at`（datetime, nullable），並 backfill 既有回覆為 `now()`（已讀）
- [x] 1.2 更新 `Reply` model：`read_at` 加入 `$fillable` 與 `casts()`（datetime）

## 2. Service 擴充

- [x] 2.1 `ReplyService` 新增 `markAsRead(int $postId, ?int $userId = null): int`，將貼文所有回覆 `read_at` 設為 `now()`，回傳更新筆數
- [x] 2.2 `ReplyService` 新增 `unreadCount(int $postId, ?int $userId = null): int`：計算貼文未讀回覆數（`read_at IS NULL`）
- [x] 2.3 `ReplyService::list()` 支援依 `post_id` 篩選；抽屜於元件層以 `sortBy('created_at')` 升序，保留 `list()` 既有降序以不影響 MCP `ListRepliesTool`

## 3. 輪詢整合

- [x] 3.1 `CollectThreadsReplies` 建立新回覆時，`read_at` 保持 null（未讀）

## 4. 抽屜 Livewire 元件

- [x] 4.1 建立 `app/Livewire/PostReplyDrawer.php`：接收 `postId`，載入該貼文回覆（舊→新），提供 `sendReply()` 方法呼叫 `ReplyService::createPostReply()`
- [x] 4.2 元件載入時呼叫 `ReplyService::markAsRead()` 將該貼文回覆標記已讀
- [x] 4.3 建立 Blade view（`resources/views/livewire/post-reply-drawer.blade.php`）：Threads 風格回覆串排版 + 下方回覆輸入框
- [x] 4.4 在 Blade view 加入 Alpine.js 捲動邏輯：載入回覆與送出回覆後自動捲動到最底部
- [x] 4.5 在回覆串顯示本機回覆的發佈狀態：`New`/`Publishing` 顯示「傳送中…」（脈動動畫）、`Replied` 顯示「已回覆」、`Failed` 顯示「發佈失敗」；輪詢回覆（Polling）不顯示狀態

## 5. 列表整合

- [x] 5.1 `ListPosts` 卡片新增「回覆」按鈕（`recordActions`），點擊後開啟抽屜並載入對應 Livewire 元件
- [x] 5.2 `ListPosts` 卡片新增「有新回覆」警示 badge，依 `ReplyService::unreadCount()` 決定顯示
- [x] 5.3 在 `ListPosts` 頁面加入抽屜容器（Blade view），容納 `PostReplyDrawer` Livewire 元件

## 6. 測試

- [x] 6.1 撰寫 `ReplyService::markAsRead` 與 `unreadCount` 的 feature test
- [x] 6.2 撰寫 `PostReplyDrawer` Livewire 元件的測試（載入回覆、標記已讀、回覆貼文）
- [x] 6.3 撰寫 `ListPosts` 顯示「回覆」按鈕與「有新回覆」警示的測試
- [x] 6.4 執行相關測試確認通過

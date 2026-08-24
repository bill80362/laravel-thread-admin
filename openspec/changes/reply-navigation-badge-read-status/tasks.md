## 1. ReplyService 全域統計方法

- [x] 1.1 新增 `ReplyService::unreadTotalCount(?int $userId = null): int`：查詢該使用者所有未讀回覆總數
- [x] 1.2 新增 `ReplyService::totalCount(?int $userId = null): int`：查詢該使用者回覆總數
- [x] 1.3 新增 `ReplyService::markAllAsRead(?int $userId = null): int`：將該使用者所有未讀回覆標記已讀
- [x] 1.4 撰寫 `ReplyService` 三個新方法的 feature test

## 2. 側邊欄 Navigation Badge

- [x] 2.1 `ReplyResource` 新增 `getNavigationBadge()`：回傳 `${unreadTotalCount}/${totalCount}` 格式字串
- [x] 2.2 `ReplyResource` 新增 `getNavigationBadgeColor()`：有未讀回覆時回傳 `warning`，全部已讀時回傳 `gray`

## 3. 進入回覆面板自動標記已讀

- [x] 3.1 `ListReplies::mount()` 中調用 `ReplyService::markAllAsRead()`，確保進入頁面時全數已讀
- [x] 3.2 確認側邊欄 badge 在頁面切換後自動更新為 `0/總數`

## 4. MCP ListPostsTool 回傳回覆數量

- [x] 4.1 `ListPostsTool` 的 map 回傳中新增 `reply_unread_count`（`ReplyService::unreadCount($post->id)`）
- [x] 4.2 `ListPostsTool` 的 map 回傳中新增 `reply_total_count`（`$post->replies()->count()`）
- [x] 4.3 更新 `ListPostsTool` 的 schema description 說明新欄位含義

## 5. MCP ListRepliesTool 回傳 read_at 與標記已讀

- [x] 5.1 `ListRepliesTool` 的 map 回傳中新增 `read_at` 欄位
- [x] 5.2 `ListRepliesTool` 的 schema 新增可選參數 `mark_as_read`（boolean）
- [x] 5.3 `ListRepliesTool::handle()` 在查詢後依 `mark_as_read` 參數決定是否調用 `ReplyService::markAllAsRead()`

## 6. 驗證既有測試不受影響

- [x] 6.1 執行 `php artisan test --compact` 確認所有既有測試通過

## 1. 移除側邊欄 Navigation Badge

- [x] 1.1 移除 `ReplyResource::getNavigationBadge()` 方法
- [x] 1.2 移除 `ReplyResource::getNavigationBadgeColor()` 方法

## 2. 貼文卡片「回覆」按鈕顯示計數

- [x] 2.1 在 `ListPosts.php` 的 `viewReplies` action 中，將 `->label('回覆')` 改為 closure，使用 `ReplyService::unreadCount()` 與 `ReplyService::totalCountForPost()` 動態生成 `回覆 (<未讀>/<總數>)` 格式，總數為 0 時僅顯示 `回覆`

## 3. 驗證

- [x] 3.1 開啟貼文列表頁面，確認左側選單「回覆面板」不再顯示 badge 數字
- [x] 3.2 確認已經有回覆的貼文卡片「回覆」按鈕顯示 `回覆 (X/Y)` 格式
- [x] 3.3 確認沒有回覆的貼文卡片「回覆」按鈕僅顯示 `回覆`
- [x] 3.4 執行 `php artisan test --compact --filter=ReplyService` 確保測試全部通過

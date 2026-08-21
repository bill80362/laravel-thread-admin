## 1. 修正輪詢建立回覆的 user_id

- [x] 1.1 在 `CollectThreadsReplies.php` 的 `collectForAccount()` 中，於 `firstOrCreate` 建立欄位補上 `'user_id' => $account->user_id`

## 2. 回填既有資料

- [x] 2.1 建立 migration，將 `replies.user_id` 為 `null` 的記錄，依 `threads_account_id` 對應的 `threads_accounts.user_id` 回填

## 3. 測試

- [x] 3.1 在 `tests/Feature/CollectThreadsRepliesTest.php` 新增測試，驗證輪詢建立的回覆帶有正確的 `user_id`
- [x] 3.2 執行相關測試確認通過

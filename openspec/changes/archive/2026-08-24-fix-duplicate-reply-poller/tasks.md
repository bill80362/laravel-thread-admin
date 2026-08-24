## 1. 回寫 threads_reply_id

- [x] 1.1 在 `PublishReply::handle()` 的 publish 成功區塊中，將 `$threads->publishContainer()` 的回傳值存入 `$reply->threads_reply_id`
- [x] 1.2 執行 `php artisan test --compact --filter=PublishReply` 確認現有測試仍通過
- [x] 1.3 執行 `php artisan test --compact --filter=CollectThreadsReplies` 確認排程相關測試仍通過

## 2. 清理現有重複資料指令

- [x] 2.1 建立 Artisan 指令 `php artisan replies:deduplicate`，掃描所有 status=Replied 但 threads_reply_id 為 null 的回覆，尋找同一 post_id、相同 text、source=polling 的重複記錄並刪除
- [x] 2.2 測試該指令於本地資料庫執行無誤

## 3. 執行清理與驗證

- [ ] 3.1 部署後於正式環境執行 `php artisan replies:deduplicate` 清理 post_id=21 的重複資料
- [ ] 3.2 監控排程下次執行時不再重複匯入同一則回覆

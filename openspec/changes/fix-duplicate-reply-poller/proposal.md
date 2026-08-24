## Why

手動回覆 Threads 貼文後，排程抓取回覆的 Job（`CollectThreadsReplies`）會將同一則回覆再次抓入系統，導致資料重複。這是因為 `PublishReply` 發佈成功後沒有將 Threads API 回傳的 media ID 回寫到 `reply.threads_reply_id`，使得排程的 `firstOrCreate` 無法辨識這則回覆已存在。

## What Changes

- **`PublishReply` 發佈成功後回寫 `threads_reply_id`**：在 `publishContainer()` 成功後，將 Threads API 回傳的 media ID 存入 `$reply->threads_reply_id`，讓 `CollectThreadsReplies` 的 `firstOrCreate` 能正確跳過。
- **無需變更 `CollectThreadsReplies` 邏輯**，因為該 Job 已使用 `firstOrCreate(['threads_reply_id' => ...])`，只要 `threads_reply_id` 有值即可自動去重。

## Capabilities

### New Capabilities

無

### Modified Capabilities

- `reply-publishing`: 發佈回覆後需將 Threads media ID 記錄回本地資料庫，以確保排程抓取不重複匯入

## Impact

- `app/Jobs/PublishReply.php`：約 1-2 行變更，publish 成功後回寫 ID
- `app/Models/Reply.php`：欄位已存在 `threads_reply_id`，無需 Migration

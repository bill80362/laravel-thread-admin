## Why

`CollectThreadsReplies` 輪詢抓回 Threads 回覆時，`firstOrCreate` 未設定 `user_id`，導致抓回的回覆 `user_id` 為 `null`。後台回覆面板依 `user_id = auth()->id()` 過濾，因此這些回覆不會顯示，看起來就像「回覆沒有被抓回來」。

## What Changes

- 修正 `CollectThreadsReplies::collectForAccount()`，在建立回覆時補上 `user_id`（取自帳號所屬使用者）。
- 修正既有已抓回但 `user_id` 為 `null` 的回覆資料，依其 `threads_account_id` 對應的帳號 `user_id` 回填。
- 補強測試，確保輪詢建立的回覆帶有正確的 `user_id`。

## Capabilities

### New Capabilities
<!-- 無新增能力 -->

### Modified Capabilities
- `data-isolation`: 強化「回覆歸屬於使用者」的 requirement，明確涵蓋輪詢（Polling）來源的回覆也必須記錄 `user_id`，確保後台與 MCP 皆能正確隔離與顯示。

## Impact

- `app/Jobs/CollectThreadsReplies.php`：建立回覆時補上 `user_id`。
- `tests/Feature/CollectThreadsRepliesTest.php`：新增/調整測試。
- 資料庫既有資料：需回填 `replies.user_id`（一次性資料修正）。

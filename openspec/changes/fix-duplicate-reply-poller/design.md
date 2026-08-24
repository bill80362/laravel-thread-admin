## Context

此變更的動機詳見 `proposal.md - Why`。目前 `CollectThreadsReplies` 使用 `firstOrCreate(['threads_reply_id' => ...])` 來避免重複，但 `PublishReply` 發佈成功後未回寫此欄位，因此排程無法辨識自己發出的回覆。

現有 publish 流程（`PublishReply::handle()`）在第二階段（publish 完成）有以下內容：

```php
$threads->publishContainer($account, $this->creationId);

$reply->update([
    'status' => ReplyStatus::Replied,
    'replied_at' => now(),
    'error_message' => null,
]);
```

`publishContainer()` 回傳的是 Threads 上的 media ID，但目前被棄置未用。

## Goals / Non-Goals

**Goals:**
- `PublishReply` 發佈成功後將 Threads media ID 回寫到 `reply.threads_reply_id`
- 確保 `CollectThreadsReplies` 可正確辨識並跳過已發佈的回覆
- 修復現有資料中已重複的記錄

**Non-Goals:**
- 不改變 `CollectThreadsReplies` 的邏輯（已正確使用 `firstOrCreate`）
- 不新增欄位或 Migration
- 不改變回覆發佈流程的錯誤處理邏輯

## Decisions

| 決策 | 選擇 | 替代方案 | 理由 |
|------|------|---------|------|
| 何處回寫 ID | `PublishReply::handle()` 的 publish 成功區塊 | 在 `ReplyService::createPostReply()` 中處理 publish 回呼 | 發佈 Job 是唯一知道發佈結果的環節，在此回寫最直接 |
| 回寫的欄位 | 既有 `threads_reply_id` | 新增 `published_media_id` 等欄位 | 語意已吻合，無需 Migration |
| 處理現有重複資料 | 新增 Artisan 指令清除重複 | 寫 tinker 手動清理 | 保留 Artisan 指令便於未來再次執行 |

## Risks / Trade-offs

- **現有已重複的資料**：執行清理指令時需注意僅刪除 source=polling 且與某筆 manual + replied 回覆完全相同的重複記錄，避免誤刪正常回覆。→ 清理指令採保守比對（相同 post_id、相同 text、相同 threads_account_id、相同 author_username、且對應的 manual 回覆已有 threads_reply_id 者才刪除）
- **回寫時機點**：僅在 publish 最終成功（non-retryable 錯誤不在此列）時才回寫。若 publish 最終失敗，不會回寫，故排程未來仍可抓取並再次嘗試關聯。→ 此行為符合預期，無需變更。

## Migration Plan

1. 修改 `PublishReply::handle()` 在 publish 成功後加上一行回寫 `threads_reply_id`
2. 建立 Artisan 指令 `php artisan replies:deduplicate`，掃描所有 status=Replied 但 `threads_reply_id` 為 null 的回覆：
   - 於 `replies` 表中尋找同一 post_id、相同 text、source=polling 的重複記錄
   - 刪除這些重複記錄
3. 部署後執行一次該指令清理現有資料

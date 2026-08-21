## Context

回覆同步間隔由 `CollectThreadsReplies::SYNC_INTERVAL_MINUTES` 常數控制（目前為 5）。此常數同時被 `RepliesSyncNotice` Widget 動態讀取，用於顯示同步間隔文字。使用說明（`usage-guide`）的 chapter4 則為硬編碼的「5 分鐘」文字。

## Goals / Non-Goals

**Goals:**
- 將回覆同步間隔從 5 分鐘縮短至 2 分鐘。
- 確保所有對外顯示的同步間隔文字與實際常數一致。

**Non-Goals:**
- 不改變輪詢機制本身（端點、欄位、去重邏輯）。
- 不調整排程觸發頻率（仍為每分鐘派發 job，由 `last_synced_at` 判斷是否執行）。

## Decisions

### 調整 `SYNC_INTERVAL_MINUTES` 為 2
單一常數調整即可改變同步頻率。抓回覆（`GET /{media_id}/replies`）屬於讀取操作，不受發文/發回覆的 publishing rate limit（250 篇 / 1,000 則，皆為 24h）限制，而是受「App 呼叫次數」（`4800 × impressions` / 24h）限制。每日每帳號約 720 次讀取，對 App 呼叫次數而言在安全範圍內。

### 同步更新使用說明硬編碼文字
`chapter4.blade.php` 的「5 分鐘」改為「2 分鐘」。`replies-sync-notice.blade.php` 已動態讀取 `$syncInterval`，無需修改。

## Risks / Trade-offs

- **App 呼叫次數增加**（288→720 次/日/帳號）→ 受 `4800 × impressions` 限制，一般帳號曝光量下遠低於上限；但多帳號時需留意總量，若未來帳號數增加可再評估。
- **使用說明與常數可能再次不同步** → 目前 chapter4 為硬編碼，未來調整常數時需同步更新（既有已知限制）。

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
單一常數調整即可改變同步頻率。每日每帳號 API 呼叫約 720 次，仍在 Threads 回覆 rate limit（1,000 次/24h）內。

### 同步更新使用說明硬編碼文字
`chapter4.blade.php` 的「5 分鐘」改為「2 分鐘」。`replies-sync-notice.blade.php` 已動態讀取 `$syncInterval`，無需修改。

## Risks / Trade-offs

- **API 呼叫次數增加**（288→720 次/日/帳號）→ 仍在 rate limit 內，但多帳號時需留意總量；若未來帳號數增加可再評估。
- **使用說明與常數可能再次不同步** → 目前 chapter4 為硬編碼，未來調整常數時需同步更新（既有已知限制）。

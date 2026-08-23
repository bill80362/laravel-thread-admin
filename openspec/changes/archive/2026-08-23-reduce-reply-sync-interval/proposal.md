## Why

回覆同步間隔目前為 5 分鐘，使用者希望更即時地看到新回覆。調成 2 分鐘可將回覆延遲從最慢 5 分鐘縮短至 2 分鐘。抓回覆（`GET /replies`）屬讀取操作，不受發文/發回覆的 publishing rate limit（250 篇 / 1,000 則，24h）限制，而是受「App 呼叫次數」（`4800 × impressions` / 24h）限制；每日每帳號約 720 次讀取仍在安全範圍內。

## What Changes

- 將 `CollectThreadsReplies::SYNC_INTERVAL_MINUTES` 從 `5` 調整為 `2`。
- 同步更新使用說明（`usage-guide`）與回覆同步提示（`replies-sync-notice`）中的「5 分鐘」文字為「2 分鐘」。

## Capabilities

### New Capabilities
<!-- 無新增能力 -->

### Modified Capabilities
- `replies-sync-notice`: 回覆同步提示的間隔文字由「每 5 分鐘」改為「每 2 分鐘」。
- `usage-guide`: 使用說明中回覆收集頻率由「每 5 分鐘」改為「每 2 分鐘」。

## Impact

- `app/Jobs/CollectThreadsReplies.php`：`SYNC_INTERVAL_MINUTES` 常數由 5 改為 2。
- `resources/views/filament/pages/usage-guide/chapter4.blade.php`：收集頻率文字更新。
- `resources/views/filament/widgets/replies-sync-notice.blade.php`：此處已動態讀取 `$syncInterval`，無需修改，但需確認。

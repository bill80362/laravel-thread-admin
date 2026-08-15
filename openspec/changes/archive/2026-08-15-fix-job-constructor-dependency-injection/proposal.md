## Why

`CollectThreadsReplies` 和 `RefreshThreadsTokens` 兩個 Job 在建構子中依賴注入 `ThreadsClient`，但 scheduler 透過 `::dispatch()` 派發時未傳入參數，導致 queue worker 在反序列化時因參數不足而報錯。其中 `CollectThreadsReplies` 每分鐘觸發一次，日誌已累積大量重複錯誤。

## What Changes

- `CollectThreadsReplies`：移除建構子的 `ThreadsClient` 參數，改由 `handle()` 方法注入
- `RefreshThreadsTokens`：同上
- 兩個 Job 的行為完全不變，僅修正依賴注入位置

## Capabilities

### New Capabilities
<!-- 無新能力，此為純 bug 修復 -->

### Modified Capabilities
<!-- 無現有能力變更，行為不變 -->

## Impact

- `app/Jobs/CollectThreadsReplies.php` — 建構子與 `handle()` 簽章變更
- `app/Jobs/RefreshThreadsTokens.php` — 建構子與 `handle()` 簽章變更
- `app/Console/Commands/RunThreadsScheduler.php` — 無需變更（dispatch 呼叫本身已不傳參數）
- 現有測試 `tests/Feature/CollectThreadsRepliesTest.php` 需同步調整建構子呼叫方式

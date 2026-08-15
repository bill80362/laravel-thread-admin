## Why

`threads:schedule` 排程在 `CollectThreadsReplies` Job 建構失敗時直接拋出 ArgumentCountError 導致 exit code 1，整個排程崩潰——不只是 reply 收集失敗，連 `dispatchDuePosts` 和 `dispatchTokenRefresh` 也一起被阻斷。同時，應用程式時區設定為 UTC，導致 `scheduled_at` 與台灣使用者直覺不符，容易造成排程時間混淆。

## What Changes

- 將 `config/app.php` 的 `timezone` 從 `UTC` 改為 `Asia/Taipei`
- 在 `RunThreadsScheduler::handle()` 中對各 dispatch 方法加入 try-catch，確保單一 Job dispatch 失敗不影響其他排程任務

## Capabilities

### New Capabilities
- `scheduler-resilience`: 排程任務的容錯能力——任一 Job 的 dispatch 失敗不得阻斷其他排程任務的執行

### Modified Capabilities
無（時區變更屬於配置層級，不涉及規格變動）

## Impact

- `config/app.php` — `timezone` 設定值變更
- `app/Console/Commands/RunThreadsScheduler.php` — 新增 try-catch 容錯邏輯
- 所有依賴 `now()` / `Carbon` 的時間計算將使用台灣時區（UTC+8）

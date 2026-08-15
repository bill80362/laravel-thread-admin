## 1. 修正 CollectThreadsReplies Job

- [x] 1.1 移除建構子的 `ThreadsClient` 參數，改為無參數建構子
- [x] 1.2 將 `ThreadsClient` 加入 `handle()` 方法簽章：`handle(ThreadsClient $threads)`

## 2. 修正 RefreshThreadsTokens Job

- [x] 2.1 移除建構子的 `ThreadsClient` 參數，改為無參數建構子
- [x] 2.2 將 `ThreadsClient` 加入 `handle()` 方法簽章：`handle(ThreadsClient $threads)`

## 3. 更新測試

- [x] 3.1 更新 `CollectThreadsRepliesTest`：將 `new CollectThreadsReplies($threads)` 改為 `new CollectThreadsReplies()`，並在呼叫 `handle()` 前用 `$this->app->instance(ThreadsClient::class, $threads)` 綁定 mock

## 4. 驗證

- [x] 4.1 執行 `CollectThreadsRepliesTest` 確保所有測試通過
- [x] 4.2 執行 `vendor/bin/pint --dirty --format agent` 確保程式碼風格一致

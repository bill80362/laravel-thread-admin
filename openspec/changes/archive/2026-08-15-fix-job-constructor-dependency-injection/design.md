## Context

`PublishScheduledPost` 已採用正確的 Laravel Queueable Job 模式：建構子只收序列化資料，`ThreadsClient` 放在 `handle()` 由 container 注入。但 `CollectThreadsReplies` 和 `RefreshThreadsTokens` 仍把 `ThreadsClient` 放在建構子，導致 `::dispatch()` 無參數時反序列化失敗。

## Goals / Non-Goals

**Goals:**
- 修正 `CollectThreadsReplies` 和 `RefreshThreadsTokens` 的依賴注入位置
- 保持行為完全不變

**Non-Goals:**
- 不調整 `RunThreadsScheduler` 的 dispatch 邏輯
- 不改變任何業務邏輯

## Decisions

**決策：將 `ThreadsClient` 從建構子移到 `handle()` 方法參數**

- 理由：Laravel 在呼叫 `handle()` 時會透過 container 自動解析方法參數，這是 Queueable Job 的標準模式
- 替代方案：在 `dispatchReplyCollection()` 中傳入 `app(ThreadsClient::class)` — 但這會讓呼叫端需要知道依賴，且 `CollectThreadsReplies` 建構子無資料參數時，用方法注入更簡潔
- 參照：`PublishScheduledPost::handle(ThreadsClient $threads)` 已使用此模式

**決策：測試改用 `$this->app->instance()` 綁定 mock**

- 測試目前用 `new CollectThreadsReplies($threads)` 傳入 mock
- 改為 `new CollectThreadsReplies()`，並在呼叫 `handle()` 前 `$this->app->instance(ThreadsClient::class, $threads)`
- 理由：`handle()` 的參數由 container 解析，測試需將 mock 綁入 container

## Risks / Trade-offs

- 低風險：兩個 Job 行為完全不變，僅調整注入位置
- 測試覆蓋：`CollectThreadsReplies` 有完整測試，`RefreshThreadsTokens` 無測試（可後續補）
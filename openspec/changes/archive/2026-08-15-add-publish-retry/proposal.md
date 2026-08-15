## Why

排程發文遇到 Threads API 的暫時性錯誤（實測案例：「The requested resource does not exist」，稍後重試即成功）時，`PublishScheduledPost` 目前一律將貼文直接標記為 `failed`，沒有任何重試機制。這類錯誤非永久性失敗，卻讓貼文永久停留在失敗狀態，需人工重新排程。

## What Changes

- 在 `ThreadsApiException` 新增 `isRetryable()` 方法，用於判斷錯誤是否為暫時性（HTTP 5xx、網路錯誤、`resource does not exist` 等）。
- `PublishScheduledPost` 針對可重試錯誤自動重試（有限次數 + 退避延遲），超過重試上限才標記 `failed`。
- 永久性錯誤（token 失效、rate limit）維持現行行為，直接標記 `failed` 不重試。
- 補齊對應的單元/功能測試。

## Capabilities

### New Capabilities
- `publish-retry`: 排程發文在遇到暫時性 API 錯誤時的自動重試行為（重試次數、退避、超過上限後的失敗處理）。

### Modified Capabilities
（無）

## Impact

- `app/Exceptions/ThreadsApiException.php` — 新增 `isRetryable()` 判斷邏輯
- `app/Jobs/PublishScheduledPost.php` — 引入重試機制
- `tests/Feature/PublishScheduledPostTest.php` — 新增重試相關測試

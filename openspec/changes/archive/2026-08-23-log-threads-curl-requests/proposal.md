## Why

排程發文（`PublishScheduledPost`）透過 `ThreadsClient` 呼叫 Threads Graph API 時，若失敗僅在 `posts.error_message` 記錄簡短錯誤訊息，缺乏完整的請求內容（URL、參數、回應），難以診斷 API 錯誤的實際原因。需要暫時性地將完整 curl 內容記錄到 log，方便除錯。

## What Changes

- 在 `ThreadsClient::request()` 中，將每次 API 呼叫的完整 curl 內容（method、URL、query/form 參數）記錄到 Laravel log。
- 記錄內容包含請求方法、完整 URL、參數，以及（若有的話）回應狀態碼與回應 body。
- 此為暫時性除錯功能，僅記錄到 log，不影響既有發文流程與錯誤處理。

## Capabilities

### New Capabilities
- `threads-curl-logging`: 將 Threads API 請求的完整 curl 內容記錄到 Laravel log，供除錯使用。

### Modified Capabilities
（無）

## Impact

- `app/Services/ThreadsClient.php` — 在 `request()` 中新增 curl 內容記錄
- `tests/Unit/ThreadsClientTest.php` — 新增 curl 記錄相關測試

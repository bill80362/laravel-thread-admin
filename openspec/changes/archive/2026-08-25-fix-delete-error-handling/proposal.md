## Why

刪除貼文失敗時，`ThreadsApiException::isTokenInvalid()` 會因錯誤訊息中包含 `access_token` URL 參數而誤判為 token 失效，導致帳號被錯誤標記為「需重新授權」。同時，`ThreadsClient::request()` 在遇到 5xx 錯誤時未正確記錄 response body 且未傳遞 HTTP status code，造成除錯困難。

## What Changes

- 強化 `isTokenInvalid()` 的判斷邏輯，避免因 URL 中的 `access_token` 查詢參數而誤判
- `request()` 方法中對 GuzzleException（非 ClientException）的 5xx 錯誤也記錄 response body
- `request()` 方法中對 GuzzleException 正確傳遞 HTTP status code 給 `ThreadsApiException`

## Capabilities

### New Capabilities

無 — 純 bug 修復，無新增功能。

### Modified Capabilities

無 — 行為不變，僅修正錯誤判斷邏輯。

## Impact

- `app/Exceptions/ThreadsApiException.php` — `isTokenInvalid()` 方法
- `app/Services/ThreadsClient.php` — `request()` 方法中 `GuzzleException` 的 catch 區塊

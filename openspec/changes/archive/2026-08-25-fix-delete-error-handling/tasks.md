## 1. 強化 isTokenInvalid() 判斷邏輯

- [x] 1.1 修改 `ThreadsApiException::isTokenInvalid()`，移除 `str_contains(strtolower($message), 'token')` 判斷，避免因 URL 中的 `access_token` 參數誤判
- [x] 1.2 確認 `isTokenInvalid()` 只保留 HTTP 401、error code 190、`oauth` 字樣判斷

## 2. 強化 request() 的 5xx 錯誤處理

- [x] 2.1 在 `ThreadsClient::request()` 的 `GuzzleException` catch 分支，使用 `RequestException` 判斷並讀取 `getResponse()`
- [x] 2.2 記錄 5xx response body 與 status code 至 log
- [x] 2.3 將 HTTP status code 傳遞給 `ThreadsApiException`

## 3. 驗證

- [x] 3.1 執行既有測試確認無回歸
- [x] 3.2 若無相關測試，手動確認 500 錯誤不再被 `.isTokenInvalid()` 誤判

## 1. 實作 curl 記錄

- [x] 1.1 在 `ThreadsClient::request()` 中，於發送請求前記錄完整 curl 內容（method、完整 URL、參數）
- [x] 1.2 在 catch `ClientException` 時，記錄回應狀態碼與 body
- [x] 1.3 在 `decode()` 偵測到 API error 時，記錄回應狀態碼與 body

## 2. 測試

- [x] 2.1 新增測試：成功請求會記錄 curl 內容
- [x] 2.2 新增測試：失敗請求會記錄回應狀態碼與 body

## 3. 驗證

- [x] 3.1 執行 `vendor/bin/pint --dirty --format agent` 格式化
- [x] 3.2 執行相關測試（`ThreadsClientTest`）確認全部通過

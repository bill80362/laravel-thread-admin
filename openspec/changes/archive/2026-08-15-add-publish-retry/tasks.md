## 1. 資料庫遷移

- [x] 1.1 新增 migration，為 `posts` 表加入 `publish_attempts`（integer, default 0）
- [x] 1.2 更新 `Post` model 的 `$fillable` 加入 `publish_attempts`，並執行 migration

## 2. 錯誤分類邏輯

- [x] 2.1 在 `ThreadsApiException` 新增 `isRetryable()` 方法（HTTP ≥ 500、無 status 的網路錯誤、message 含 `resource does not exist`）

## 3. 重試機制

- [x] 3.1 在 `PublishScheduledPost` 新增 `MAX_PUBLISH_ATTEMPTS` 常數與退避計算
- [x] 3.2 修改 `handle()`：catch 到 `isRetryable()` 錯誤時，若 `publish_attempts < MAX` 則遞增計數並以 `delay()` 重新 dispatch
- [x] 3.3 確保永久性錯誤（token 失效、rate limit）維持現行不重試行為

## 4. 測試

- [x] 4.1 新增測試：暫時性錯誤且未達上限時，重新 dispatch 並遞增 `publish_attempts`
- [x] 4.2 新增測試：暫時性錯誤且已達上限時，標記 `failed`
- [x] 4.3 新增測試：永久性錯誤不重試（token 失效、rate limit）
- [x] 4.4 更新既有測試確認不受影響

## 5. 驗證

- [x] 5.1 執行 `vendor/bin/pint --dirty --format agent` 格式化
- [x] 5.2 執行相關測試（`PublishScheduledPostTest`）確認全部通過

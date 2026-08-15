## 1. 修復 ThreadsClient OAuth 端點

- [x] 1.1 在 `ThreadsClient` 中新增 `OAUTH_BASE` 常數
- [x] 1.2 修改 `exchangeCodeForShortToken` 使用 `OAUTH_BASE`
- [x] 1.3 修改 `exchangeShortForLongToken` 使用 `OAUTH_BASE`
- [x] 1.4 修改 `refreshLongLivedToken` 使用 `OAUTH_BASE`

## 2. 加入錯誤日誌

- [x] 2.1 在 `ThreadsOAuthController::callback()` 的 catch 區塊加入 `Log::error()`

## 3. 驗證

- [x] 3.1 執行 `vendor/bin/pint --format agent` 確認格式
- [ ] 3.2 於瀏覽器測試 OAuth 綁定流程

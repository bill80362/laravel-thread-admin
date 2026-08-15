## 1. 時區設定

- [x] 1.1 修改 `config/app.php`，將 `timezone` 從 `'UTC'` 改為 `'Asia/Taipei'`

## 2. 排程容錯

- [x] 2.1 在 `dispatchDuePosts()` 加入 try-catch，catch 到例外時以 `Log::warning()` 記錄錯誤
- [x] 2.2 在 `dispatchReplyCollection()` 加入 try-catch，catch 到例外時以 `Log::warning()` 記錄錯誤
- [x] 2.3 在 `dispatchTokenRefresh()` 加入 try-catch，catch 到例外時以 `Log::warning()` 記錄錯誤

## 3. 驗證與格式化

- [x] 3.1 執行 `vendor/bin/pint --format agent` 確保程式碼風格一致
- [x] 3.2 執行 `php artisan threads:schedule` 確認命令正常運作不拋例外
- [x] 3.3 執行 `php artisan test --compact` 確認所有測試通過

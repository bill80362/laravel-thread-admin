## 1. 移除錯誤的 CreateAction

- [x] 1.1 將 `app/Filament/Resources/ThreadsAccounts/Pages/ListThreadsAccounts.php` 的 `getHeaderActions()` 改為回傳空陣列
- [x] 1.2 執行 `vendor/bin/pint` 確認格式
- [ ] 1.3 於瀏覽器確認帳號管理頁面不再出現「新增」按鈕，僅保留「綁定 Threads 帳號」

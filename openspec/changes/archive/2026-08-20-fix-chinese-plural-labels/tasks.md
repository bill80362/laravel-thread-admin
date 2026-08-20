## 1. 修正 Resource 複數標籤

- [x] 1.1 `ThreadsAccountResource` 新增 `$pluralModelLabel = 'Threads 帳號'`
- [x] 1.2 `PostResource` 新增 `$pluralModelLabel = '貼文'`
- [x] 1.3 `UserResource` 新增 `$pluralModelLabel = '使用者'`

## 2. 驗證

- [x] 2.1 執行 `vendor/bin/pint --dirty --format agent` 確保程式碼格式正確
- [x] 2.2 手動檢查各 List 頁面標題與麵包屑不再出現多餘的 "s"

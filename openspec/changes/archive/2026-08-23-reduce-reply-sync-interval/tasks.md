## 1. 調整同步間隔

- [x] 1.1 將 `app/Jobs/CollectThreadsReplies.php` 的 `SYNC_INTERVAL_MINUTES` 常數由 `5` 改為 `2`

## 2. 更新使用說明

- [x] 2.1 將 `resources/views/filament/pages/usage-guide/chapter4.blade.php` 的「5 分鐘」改為「2 分鐘」
- [x] 2.2 確認 `resources/views/filament/widgets/replies-sync-notice.blade.php` 已動態讀取 `$syncInterval`，無需修改

## 3. 驗證

- [x] 3.1 執行相關測試確認通過

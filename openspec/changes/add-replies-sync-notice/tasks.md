## 1. 回覆同步說明 Widget

- [x] 1.1 建立 Widget 類別 `app/Filament/Resources/Replies/Widgets/RepliesSyncNotice.php`，繼承 `Filament\Widgets\Widget`，指定自訂 view 為 `filament.widgets.replies-sync-notice`，並動態帶入 `CollectThreadsReplies::SYNC_INTERVAL_MINUTES` 常數作為同步間隔
- [x] 1.2 建立 Blade 模板 `resources/views/filament/widgets/replies-sync-notice.blade.php`，使用 Filament 既有元件（`filament::` 前綴）渲染資訊提示，文案說明「回覆資料每 N 分鐘自動同步一次，新留言可能不會立即顯示」
- [x] 1.3 在 `app/Filament/Resources/Replies/Pages/ListReplies.php` 新增 `getHeaderWidgets()` 掛載 `RepliesSyncNotice`

## 2. 複數標籤修正

- [x] 2.1 在 `app/Filament/Resources/Replies/ReplyResource.php` 新增 `protected static ?string $pluralModelLabel = '回覆';`

## 3. 導覽選單排序

- [x] 3.1 在 `ThreadsAppResource` 新增 `$navigationSort = 10`
- [x] 3.2 在 `ThreadsAccountResource` 新增 `$navigationSort = 20`
- [x] 3.3 在 `PostResource` 新增 `$navigationSort = 30`
- [x] 3.4 在 `ReplyResource` 新增 `$navigationSort = 40`

## 4. 驗證

- [x] 4.1 執行 `vendor/bin/pint --dirty --format agent` 修正格式
- [x] 4.2 執行相關 feature test 確認無回歸
- [ ] 4.3 於 `/admin/replies` 手動確認：標題顯示「回覆」、上方出現同步說明、左側選單順序正確

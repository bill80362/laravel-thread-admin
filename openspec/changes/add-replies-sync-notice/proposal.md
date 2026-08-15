## Why

回覆面板目前缺少同步機制的說明，使用者看到回覆列表時不清楚資料是定期輪詢取得、不會即時更新，容易誤以為系統故障。同時存在兩個 UI 瑕疵：複數標題顯示「回覆s」而非「回覆」，以及左側選單順序不符合操作邏輯。

## What Changes

- 在回覆列表頁（`/admin/replies`）上方新增資訊提示 Widget，說明回覆資料每 5 分鐘自動同步一次
- 修正 `ReplyResource` 的複數標籤，從自動產生的「回覆s」改為「回覆」
- 調整左側導覽選單順序為：Dashboard → APP → 帳號 → 發文 → 回覆

## Capabilities

### New Capabilities
- `replies-sync-notice`: 回覆列表頁顯示同步機制說明提示

### Modified Capabilities
- `reply-manual-create`: 修正複數標籤（`$pluralModelLabel`）與導覽排序（`$navigationSort`）

## Impact

- `app/Filament/Resources/Replies/ReplyResource.php` — 新增 `$pluralModelLabel`、`$navigationSort`
- `app/Filament/Resources/Replies/Pages/ListReplies.php` — 掛載 Header Widget
- `app/Filament/Resources/Replies/Widgets/RepliesSyncNotice.php` — 新增 Widget 類別
- `resources/views/filament/widgets/replies-sync-notice.blade.php` — 新增 Blade 模板
- `app/Filament/Resources/Posts/PostResource.php` — 新增 `$navigationSort`
- `app/Filament/Resources/ThreadsAccounts/ThreadsAccountResource.php` — 新增 `$navigationSort`
- `app/Filament/Resources/ThreadsApps/ThreadsAppResource.php` — 新增 `$navigationSort`

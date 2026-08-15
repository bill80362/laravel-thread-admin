## Why

填寫 `scheduled_at` 排程時間建立貼文後，`status` 始終保持資料庫預設值 `draft`，導致 `RunThreadsScheduler::dispatchDuePosts()` 永遠找不到這些貼文（查詢條件為 `status = 'scheduled'`），排程發文功能完全無法運作。

## What Changes

- `CreatePost`：在 `mutateFormDataBeforeCreate()` 中根據 `scheduled_at` 自動將 `status` 設為 `scheduled`
- `EditPost`：同上，在 `mutateFormDataBeforeSave()` 中處理
- 現有測試 `PostResourceTest::test_create_post_with_valid_data` 的斷言從 `Draft` 改為 `Scheduled`

## Capabilities

### New Capabilities
<!-- 無新能力，此為純 bug 修復 -->

### Modified Capabilities
<!-- 無現有能力變更 -->

## Impact

- `app/Filament/Resources/Posts/Pages/CreatePost.php` — 新增 `mutateFormDataBeforeCreate()`
- `app/Filament/Resources/Posts/Pages/EditPost.php` — 新增 `mutateFormDataBeforeSave()`
- `tests/Feature/PostResourceTest.php` — 修正斷言

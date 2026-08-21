## Why

在後台建立貼文時，若使用者新增了圖片 Repeater item 但未上傳檔案（或上傳後移除），送出表單會觸發 `SQLSTATE[23000]: Integrity constraint violation: 19 NOT NULL constraint failed: post_images.image_path` 錯誤。原因是 Filament 的 `Repeater->relationship()` 會自動設定 `dehydrated(false)`，導致 `images` 不會出現在 `getState()` 回傳的資料中，因此 `CreatePost::mutateFormDataBeforeCreate()` 中過濾空圖片的邏輯永遠不會執行，空的 `image_path` 仍被寫入 `post_images`。

## What Changes

- 在 `PostForm` 的圖片 `Repeater` 上使用 Filament 官方鉤子 `mutateRelationshipDataBeforeCreateUsing()` 與 `mutateRelationshipDataBeforeSaveUsing()`，當 item 的 `image_path` 為空時回傳 `null`，讓該 item 被跳過（不寫入 `post_images`）。
- 移除 `CreatePost::mutateFormDataBeforeCreate()` 與 `EditPost::mutateFormDataBeforeSave()` 中無效的 `images` 過濾邏輯（因 `$data['images']` 永遠為空，該邏輯從未生效）。
- 保留既有編輯限制：非 `Draft` / `Scheduled` 狀態的貼文，圖片與內文皆不可編輯（此限制已存在於 `PostForm` 各欄位的 `disabled` 條件，本次不變更）。
- 補測試：建立貼文時若 `images` 含空 `image_path` 的 item，確認不會寫入 `post_images`。

## Capabilities

### New Capabilities

（無）

### Modified Capabilities

- `multi-image-posts`: 新增需求——後台建立/編輯貼文時，空的圖片 item（未上傳檔案）不得寫入 `post_images`，且非排程中狀態的貼文圖片與內文不可編輯。

## Impact

- `app/Filament/Resources/Posts/Schemas/PostForm.php`：圖片 `Repeater` 新增 `mutateRelationshipDataBeforeCreateUsing` / `mutateRelationshipDataBeforeSaveUsing`。
- `app/Filament/Resources/Posts/Pages/CreatePost.php`：移除無效的 `images` 過濾。
- `app/Filament/Resources/Posts/Pages/EditPost.php`：移除無效的 `images` 過濾。
- `tests/Feature/PostResourceTest.php`：新增空圖片 item 的測試。

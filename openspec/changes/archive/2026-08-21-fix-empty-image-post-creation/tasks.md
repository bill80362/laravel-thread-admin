## 1. 過濾空圖片 item

- [x] 1.1 在 `app/Filament/Resources/Posts/Schemas/PostForm.php` 的圖片 `Repeater` 上新增 `mutateRelationshipDataBeforeCreateUsing()`，當 item 的 `image_path` 為空時回傳 `null`
- [x] 1.2 在 `app/Filament/Resources/Posts/Schemas/PostForm.php` 的圖片 `Repeater` 上新增 `mutateRelationshipDataBeforeSaveUsing()`，當 item 的 `image_path` 為空時回傳 `null`

## 2. 移除無效過濾邏輯

- [x] 2.1 移除 `app/Filament/Resources/Posts/Pages/CreatePost.php` 中 `mutateFormDataBeforeCreate()` 針對 `$data['images']` 的過濾程式碼
- [x] 2.2 移除 `app/Filament/Resources/Posts/Pages/EditPost.php` 中 `mutateFormDataBeforeSave()` 針對 `$data['images']` 的過濾程式碼

## 3. 測試

- [x] 3.1 在 `tests/Feature/PostResourceTest.php` 新增測試：建立貼文時 `images` 含空 `image_path` 的 item，確認貼文建立成功且 `post_images` 無記錄
- [x] 3.2 執行相關測試確認通過（`php artisan test --compact --filter=PostResourceTest`）

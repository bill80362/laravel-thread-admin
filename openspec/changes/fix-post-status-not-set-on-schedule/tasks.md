## 1. 修正 CreatePost

- [ ] 1.1 在 `CreatePost` 加入 `mutateFormDataBeforeCreate()`，有 `scheduled_at` 時將 `status` 設為 `scheduled`

## 2. 修正 EditPost

- [ ] 2.1 在 `EditPost` 加入 `mutateFormDataBeforeSave()`，邏輯同上

## 3. 更新測試

- [ ] 3.1 修正 `PostResourceTest::test_create_post_with_valid_data` 的斷言：`Draft` → `Scheduled`

## 4. 驗證

- [ ] 4.1 執行 `PostResourceTest` 確保測試通過
- [ ] 4.2 執行 `vendor/bin/pint --dirty --format agent`

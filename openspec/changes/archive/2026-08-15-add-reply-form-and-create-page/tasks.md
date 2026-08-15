## 1. ReplySource enum 新增 Manual case

- [x] 1.1 在 `ReplySource` enum 新增 `case Manual = 'manual'`

## 2. 補齊 ReplyForm 表單欄位

- [x] 2.1 新增 `Select::make('threads_account_id')` — 關聯 `threadsAccount`，必填，顯示 `@username`
- [x] 2.2 新增 `Select::make('post_id')` — 關聯 `post`，可選（nullable），顯示貼文內容摘要
- [x] 2.3 新增 `TextInput::make('author_username')` — 必填，prefix `@`
- [x] 2.4 新增 `Textarea::make('text')` — 必填，max 500 字元

## 3. 新增 CreateReply 頁面

- [x] 3.1 建立 `app/Filament/Resources/Replies/Pages/CreateReply.php`
- [x] 3.2 實作 `mutateFormDataBeforeCreate()` 自動注入 `source = ReplySource::Manual`、`status = ReplyStatus::New`
- [x] 3.3 在 `ReplyResource::getPages()` 註冊 `'create' => CreateReply::route('/create')`

## 4. 將 threads_reply_id 改為可為 null

- [x] 4.1 建立 migration `2026_08_15_142945_make_threads_reply_id_nullable_on_replies_table`，讓 `threads_reply_id` 可為 null（SQLite 採重建資料表方式）
- [x] 4.2 執行 `php artisan migrate` 並確認既有資料保留

## 5. 測試

- [x] 5.1 建立 `tests/Feature/ReplyResourceTest.php`，涵蓋：成功建立、必填驗證、500 字元上限、關聯貼文、列表顯示
- [x] 5.2 執行測試確認全部通過（5 passed）

## 6. 驗證

- [x] 6.1 執行 `vendor/bin/pint --dirty --format agent` 格式化
- [x] 6.2 於瀏覽器確認 `admin/replies/create` 可正常載入並建立回覆
- [x] 6.3 確認列表頁的「新增回覆」按鈕導向正確的建立頁面

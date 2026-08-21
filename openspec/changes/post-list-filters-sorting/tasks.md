## 1. 篩選器實作

- [x] 1.1 在 `ListPosts.php` 的 `table()` 中新增 `->filters([...])`，加入狀態 `SelectFilter`（`options(PostStatus::class)`）
- [x] 1.2 新增帳號 `SelectFilter`（`relationship('threadsAccount', 'username')`，並以 `modifyQueryUsing` 限定目前使用者的帳號）
- [x] 1.3 新增發佈時間範圍篩選器（`Filter::make('published_at_range')` + `DatePicker` schema 元件，query 依起訖範圍過濾 `published_at`）
- [x] 1.4 新增排程時間範圍篩選器（`Filter::make('scheduled_at_range')` + `DatePicker` schema 元件，query 依起訖範圍過濾 `scheduled_at`）
- [x] 1.5 新增內容關鍵字篩選器（`Filter::make('text_search')` + `TextInput` schema 元件，query 對 `text` 做 `like` 模糊搜尋）
- [x] 1.6 新增錯誤訊息關鍵字篩選器（`Filter::make('error_search')` + `TextInput` schema 元件，query 對 `error_message` 做 `like` 模糊搜尋）

## 2. 排序實作

- [x] 2.1 在 `ListPosts.php` 的 `table()` 中，為 `published_at`、`scheduled_at`、`threadsAccount.username`、`status` 欄位加上 `->sortable()`
- [x] 2.2 設定預設排序：`->defaultSort(fn (Builder $query) => $query->orderByRaw('scheduled_at IS NULL')->orderBy('scheduled_at', 'desc'))`，達成「排程時間反向、NULL 置底」

## 3. 驗證

- [x] 3.1 手動驗證各篩選器與排序功能正常運作（透過 Feature 測試驗證）
- [x] 3.2 確認預設排序為排程時間反向且 NULL 置底（`scheduled_at` 為 NOT NULL，NULL 置底作為防護措施）
- [x] 3.3 執行 `vendor/bin/pint --dirty --format agent` 確保程式碼格式符合專案規範

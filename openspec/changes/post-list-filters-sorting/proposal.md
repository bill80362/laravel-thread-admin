## Why

目前 `/user/posts` 貼文列表頁面沒有任何篩選與排序功能，當貼文數量增加時，使用者難以快速找到特定狀態、帳號或時間範圍的貼文。需要提供篩選器與排序功能，並以「排程時間反向」作為預設排序，讓使用者能快速掌握最新動態。

## What Changes

- 在貼文列表新增多種篩選器：
  - 狀態（SelectFilter，依 `PostStatus` enum）
  - 帳號（SelectFilter，依綁定的 Threads 帳號）
  - 發佈時間範圍（DateRangeFilter）
  - 排程時間範圍（DateRangeFilter）
  - 內容關鍵字（TextFilter，模糊搜尋）
  - 錯誤訊息（TextFilter，模糊搜尋）
- 篩選器以 Filament 預設的收合式篩選按鈕呈現（點開下拉選單）。
- 新增多欄位排序功能：發佈時間、排程時間、帳號、狀態。
- 預設排序改為「排程時間反向」，且 `scheduled_at` 為 NULL 的貼文排在最後。

## Capabilities

### New Capabilities
- `post-list-filters-sorting`: 貼文列表的篩選與排序能力，涵蓋多種欄位篩選器、多欄位排序，以及「排程時間反向、NULL 置底」的預設排序行為。

### Modified Capabilities
<!-- 無既有 spec 的需求變更 -->

## Impact

- `app/Filament/Resources/Posts/Pages/ListPosts.php`：新增篩選器與預設排序邏輯。
- `app/Filament/Resources/Posts/Tables/PostsTable.php`：新增各欄位排序設定（若需同步）。
- 資料庫：SQLite，需處理 `ORDER BY scheduled_at IS NULL, scheduled_at DESC` 以達成 NULL 置底。
- 不影響既有 API、MCP 或資料結構。

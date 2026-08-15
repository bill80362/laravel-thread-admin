## Why

編輯貼文頁面（`admin/posts/{id}/edit`）目前只有三個可編輯欄位（目標帳號、貼文內容、排程時間），缺少對貼文狀態、發佈時間、錯誤訊息等系統管理欄位的可見性。管理者無法在編輯頁面快速了解貼文的當前狀態與發佈結果。

## What Changes

- 在 `PostForm` 編輯表單最上方新增一個唯讀的「貼文狀態資訊」`Section`，顯示 `status`（狀態）、`published_at`（發佈時間）、`error_message`（錯誤訊息）。
- `PostStatus` enum 實作 `HasLabel` 與 `HasColor` interface，將狀態標籤與顏色邏輯從 `PostsTable` 抽至 enum 統一管理。
- 此 Section 僅在編輯頁顯示（`hiddenOn('create')`），建立頁面不受影響。

## Capabilities

### New Capabilities
（無新能力，此為 UI 增強）

### Modified Capabilities
- `post-scheduling`：編輯頁面新增唯讀狀態資訊區塊

## Impact

- `app/Enums/PostStatus.php` — 實作 `HasLabel`、`HasColor`
- `app/Filament/Resources/Posts/Schemas/PostForm.php` — 新增唯讀 Section
- `app/Filament/Resources/Posts/Tables/PostsTable.php` — 改用 enum 的 `getLabel()` / `getColor()`（可選重構）

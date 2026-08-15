## 1. PostStatus enum 實作 HasLabel 與 HasColor

- [x] 1.1 讓 `PostStatus` 實作 `Filament\Support\Contracts\HasLabel`、`Filament\Support\Contracts\HasColor`
- [x] 1.2 實作 `getLabel()`：Draft→草稿、Scheduled→排程中、Publishing→發佈中、Published→已發佈、Failed→失敗
- [x] 1.3 實作 `getColor()`：Draft→gray、Scheduled→warning、Publishing→info、Published→success、Failed→danger

## 2. 編輯頁面新增唯讀狀態區塊

- [x] 2.1 在 `PostForm::configure()` 的 `components()` 最前方新增 `Section::make('貼文狀態資訊')`，並設 `->hiddenOn('create')`
- [x] 2.2 新增狀態 `TextEntry`（`status`）使用 `->badge()`，顏色/標籤由 enum 自動提供
- [x] 2.3 新增發佈時間 `TextEntry`（`published_at`）使用 `->dateTime('Y-m-d H:i')` 與 `->placeholder('-')`
- [x] 2.4 新增錯誤訊息 `TextEntry`（`error_message`）使用 `->placeholder('-')`

## 3. 列表頁重構（移除重複邏輯）

- [x] 3.1 更新 `PostsTable` 的 `status` 欄位，移除 `formatStateUsing` 與 `color` 閉包，改由 enum 的 `HasLabel`/`HasColor` 自動提供
- [x] 3.2 確認列表頁視覺與既有行為一致（badge 顏色、中文標籤不變）

## 4. 測試

- [x] 4.1 確認現有 `PostResourceTest` 相關測試仍通過
- [x] 4.2 （視需要）新增測試：編輯頁可正常載入，狀態區塊僅在 edit 顯示、create 隱藏

## 5. 驗證

- [x] 5.1 執行 `vendor/bin/pint --dirty --format agent` 格式化
- [x] 5.2 執行相關測試確認全部通過
- [ ] 5.3 於瀏覽器確認 `admin/posts/{id}/edit` 顯示狀態區塊、建立頁無此區塊

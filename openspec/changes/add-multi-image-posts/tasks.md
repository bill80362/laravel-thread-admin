## 1. 資料庫 Migration

- [ ] 1.1 建立 `post_images` 表 migration（`post_id`, `image_path`, `sort_order`，FK cascadeOnDelete）
- [ ] 1.2 在 migration 中將既有 `posts.image_path IS NOT NULL` 的資料遷移至 `post_images`（`sort_order = 0`），然後 drop `posts.image_path` 欄位
- [ ] 1.3 執行 migration 並驗證資料遷移正確

## 2. Model 層

- [ ] 2.1 建立 `PostImage` Model（`fillable`: `post_id`, `image_path`, `sort_order`；`belongsTo Post`）
- [ ] 2.2 `Post` Model 新增 `images()` hasMany 關聯，`fillable` 移除 `image_path`
- [ ] 2.3 `Post` Model `booted()` 中確保刪除 Post 時 cascade 刪除關聯圖片檔案

## 3. ThreadsClient 擴充

- [ ] 3.1 新增 `createCarouselItemContainer(ThreadsAccount, string $imageUrl): string` 方法（`is_carousel_item=true`）
- [ ] 3.2 新增 `createCarouselContainer(ThreadsAccount, array $childrenIds, ?string $text): string` 方法（`media_type=CAROUSEL`）

## 4. PostService 調整

- [ ] 4.1 `create()` 方法新增 `image_paths` 與 `image_urls` 陣列參數支援，向後相容既有 `image_path` / `image_url`
- [ ] 4.2 驗證圖片數量上限 10 張
- [ ] 4.3 建立 Post 後批次建立 `PostImage` 記錄（含 `sort_order`）

## 5. PublishScheduledPost Job 擴充

- [ ] 5.1 新增 `$childIds` 參數（`?array`），支援三階段發佈流程
- [ ] 5.2 Stage 1：依圖片數量決定走純文字 / 單圖 / Carousel 流程
- [ ] 5.3 Stage 2（Carousel 專屬）：建立 carousel container 後 dispatch Stage 3
- [ ] 5.4 Stage 3：發佈 container（單圖與 Carousel 共用）
- [ ] 5.5 錯誤處理：Carousel 流程中任一階段失敗的 retry 邏輯

## 6. Filament 後台

- [ ] 6.1 `PostForm`：將 `FileUpload::make('image_path')` 改為 `Repeater::make('images')->relationship()` + 內部 `FileUpload`，啟用 `reorderable`、`maxItems(10)`、`orderColumn('sort_order')`
- [ ] 6.2 `PostForm`：調整驗證邏輯（圖片數量上限、文字與圖片至少一項）
- [ ] 6.3 `ListPosts`：從 Table 改為 Grid 卡片佈局，每張卡片顯示首張圖片縮圖（多圖時疊加 `+N` badge）、帳號、狀態、內容預覽、排程時間
- [ ] 6.4 `CreatePost` / `EditPost` Page：確保 mutate 邏輯相容多圖（處理 `images` 關聯而非 `image_path`）

## 7. MCP CreatePostTool

- [ ] 7.1 將 `image_url` 參數改為 `image_urls`（array of strings, 最多 10 個）
- [ ] 7.2 更新 schema 定義與 description
- [ ] 7.3 更新 `handle()` 中的驗證與 `PostService::create()` 呼叫

## 8. 測試

- [ ] 8.1 更新 `PostServiceTest`：新增多圖建立、圖片上限驗證、向後相容測試
- [ ] 8.2 更新 `PublishScheduledPostTest`：新增 Carousel 發佈成功/失敗測試
- [ ] 8.3 更新 `ThreadsClientTest`：新增 `createCarouselItemContainer`、`createCarouselContainer` 測試
- [ ] 8.4 更新 `McpToolsTest`：`image_urls` 參數測試（含上限驗證）
- [ ] 8.5 更新 `PostResourceTest`：多圖上傳與排序測試

## 9. 收尾

- [ ] 9.1 執行 `vendor/bin/pint --format agent` 格式化所有變更檔案
- [ ] 9.2 執行完整測試套件確認無回歸
- [ ] 9.3 更新使用說明頁面（`UsageGuide`）中關於多圖發文的說明

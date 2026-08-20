## MODIFIED Requirements

### Requirement: 圖片上傳

#### 前端（Filament）

- 使用 Filament `Repeater` 元件搭配內部 `FileUpload`，支援多檔上傳與拖曳排序
- 關聯名稱：`images`（對應 `post_images` 表）
- 選填（可純文字或純圖片或圖文混合）
- Disk：`public`
- 目錄：`posts`
- 接受格式：`image/jpeg`, `image/png`
- 最大檔案大小：8MB（`8192` KB）
- 最大檔案數量：10 張
- 支援圖片排序（透過 Repeater 或拖曳排序）
- 預覽：顯示已上傳圖片縮圖

#### 後端儲存

- 圖片儲存至 `storage/app/public/posts/`
- 圖片路徑與排序寫入 `post_images` 表（`image_path`, `sort_order`）
- 需執行 `php artisan storage:link` 建立 symbolic link

#### 驗證規則

- `text` 和圖片至少要有其中一個
- 若只有圖片無文字，`text` 可為 null
- 圖片數量上限 10 張

### Requirement: Threads API 圖片發佈

#### ThreadsClient::createImageContainer()

```php
public function createImageContainer(
    ThreadsAccount $account,
    string $imageUrl,
    ?string $text = null
): string
```

- 呼叫 `POST /{user-id}/threads`
- 參數：`media_type=IMAGE`, `image_url=<公開URL>`, `text=<選填>`
- 回傳 creation_id
- 用於單圖發佈（1 張圖片）

#### ThreadsClient::createCarouselItemContainer()

```php
public function createCarouselItemContainer(
    ThreadsAccount $account,
    string $imageUrl
): string
```

- 呼叫 `POST /{user-id}/threads`
- 參數：`media_type=IMAGE`, `image_url=<公開URL>`, `is_carousel_item=true`
- 回傳 creation_id
- 用於 Carousel 的每張子圖片

#### ThreadsClient::createCarouselContainer()

```php
public function createCarouselContainer(
    ThreadsAccount $account,
    array $childrenIds,
    ?string $text = null
): string
```

- 呼叫 `POST /{user-id}/threads`
- 參數：`media_type=CAROUSEL`, `children=<id1>,<id2>,...`, `text=<選填>`
- 回傳 carousel container ID

#### 圖片 URL 產生

- 使用 `Storage::disk('public')->url($imagePath)`
- 產生完整公開 URL（如 `http://domain/storage/posts/xxx.jpg`）
- 依賴 `APP_URL` 設定正確

#### PublishScheduledPost 調整

- 0 張圖片：走純文字發佈流程（`createTextContainer`）
- 1 張圖片：走單圖發佈流程（`createImageContainer` + `publishContainer`）
- 2-10 張圖片：走 Carousel 發佈流程（`createCarouselItemContainer` × N → `createCarouselContainer` → `publishContainer`）

### Requirement: 資料庫變更

#### posts 表

- Drop `image_path` 欄位（資料已遷移至 `post_images`）
- `text` 欄位為 nullable（純圖片時無文字）

#### post_images 表（新建）

- `id`：主鍵
- `post_id`：外鍵關聯 posts（ON DELETE CASCADE）
- `image_path`：圖片路徑（string）
- `sort_order`：排序（integer，預設 0）
- `created_at`、`updated_at`：時間戳記

### Requirement: MCP 支援

#### CreatePostTool 參數調整

- 移除選填參數 `image_url`（string）
- 新增選填參數 `image_urls`（array of strings, URL format）
- 若有 `image_urls` 且數量為 1 則走單圖流程
- 若有 `image_urls` 且數量為 2-10 則走 Carousel 流程
- 若無 `image_urls` 則走純文字流程（現有行為）
- MCP 客戶端需自行上傳圖片到公開 URL，再傳入 `image_urls`

#### Schema 定義

```php
'image_urls' => $schema->array()
    ->items($schema->string()->format('uri'))
    ->description('圖片公開 URL 陣列（選填，最多 10 個。若有則發佈圖文貼文）'),
```

### Requirement: Filament 表單

#### PostForm 欄位調整

- 將 `FileUpload::make('image_path')` 改為 `Repeater::make('images')->relationship()` + 內部 `FileUpload`
- 啟用 `->reorderable()` 支援拖曳排序
- `->maxItems(10)` 限制最多 10 張
- `->orderColumn('sort_order')` 對應排序欄位

#### 列表頁調整

- 從 Table 改為 Grid 卡片佈局
- 每張卡片顯示首張圖片縮圖（`sort_order = 0`）
- 多圖時在縮圖上疊加 `+N` badge
- 無圖片時顯示 placeholder
- 卡片內容：帳號、狀態 badge、內容預覽（截斷）、排程時間
- 卡片上直接放 EditAction / DeleteAction

### Requirement: 限制與注意事項

- Threads API 圖片格式限制：JPEG、PNG，最大 8MB
- 圖片 URL 必須公開可訪問（不能是 localhost 或需要認證的 URL）
- Carousel 最少 2 張、最多 20 張（本系統限制最多 10 張）
- 圖片與文字不可分開發佈（同一 container / carousel）
- 本地開發時需確保 `APP_URL` 設定正確且 storage link 已建立
- Carousel 發佈需要更多處理時間（每張圖建立 container + carousel container + publish）

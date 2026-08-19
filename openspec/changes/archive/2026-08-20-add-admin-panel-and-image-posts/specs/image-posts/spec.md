# Image Posts

圖片發文功能，允許使用者上傳圖片並透過 Threads API 發佈圖文貼文。

## 圖片上傳

### 前端（Filament）

- 使用 Filament `FileUpload` 元件
- 欄位名稱：`image`
- 選填（可純文字或純圖片或圖文混合）
- Disk：`public`
- 目錄：`posts`
- 接受格式：`image/jpeg`, `image/png`
- 最大檔案大小：8MB（`8192` KB）
- 預覽：顯示已上傳圖片縮圖

### 後端儲存

- 圖片儲存至 `storage/app/public/posts/`
- 路徑寫入 `posts.image_path`
- 需執行 `php artisan storage:link` 建立 symbolic link

### 驗證規則

- `text` 和 `image` 至少要有其中一個
- 若只有圖片無文字，`text` 可為 null

## Threads API 圖片發佈

### ThreadsClient::createImageContainer()

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

### 圖片 URL 產生

- 使用 `Storage::disk('public')->url($post->image_path)`
- 產生完整公開 URL（如 `http://domain/storage/posts/xxx.jpg`）
- 依賴 `APP_URL` 設定正確

### PublishScheduledPost 調整

```php
if ($post->image_path !== null) {
    $imageUrl = Storage::disk('public')->url($post->image_path);
    $creationId = $threads->createImageContainer($account, $imageUrl, $post->text);
} else {
    $creationId = $threads->createTextContainer($account, $post->text);
}
```

## 資料庫變更

### posts 表

- 新增 `image_path` 欄位（nullable string）
- `text` 欄位改為 nullable（純圖片時無文字）

## MCP 支援

### CreatePostTool 新增參數

- 新增選填參數 `image_url`（string, URL format）
- 若有 `image_url` 則建立 Image Container
- 若無 `image_url` 則建立 Text Container（現有行為）
- MCP 客戶端需自行上傳圖片到公開 URL，再傳入 `image_url`

### Schema 定義

```php
'image_url' => $schema->string()
    ->description('圖片公開 URL（選填，若有則發佈圖文貼文）'),
```

## Filament 表單

### PostForm 新增欄位

```php
FileUpload::make('image')
    ->label('圖片')
    ->image()
    ->disk('public')
    ->directory('posts')
    ->acceptedFileTypes(['image/jpeg', 'image/png'])
    ->maxSize(8192)
    ->helperText('支援 JPEG、PNG，最大 8MB。文字與圖片至少需填寫一項。'),
```

### PostsTable 新增欄位

```php
ImageColumn::make('image_path')
    ->label('圖片')
    ->disk('public')
    ->placeholder('無圖片'),
```

## 限制與注意事項

- Threads API 圖片格式限制：JPEG、PNG，最大 8MB
- 圖片 URL 必須公開可訪問（不能是 localhost 或需要認證的 URL）
- 圖片與文字不可分開發佈（同一 container）
- 本地開發時需確保 `APP_URL` 設定正確且 storage link 已建立

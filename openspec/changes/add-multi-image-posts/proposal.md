## Why

目前發文僅支援單張圖片（`posts.image_path` 單一欄位），無法滿足需要一次發佈多張圖片（輪播/Carousel）的使用情境。Threads API 已原生支援 Carousel（最多 20 張），本專案應補齊此功能，並限制最多 10 張以符合業務需求。

## What Changes

- **新增** `post_images` 資料表，支援一對多圖片關聯與排序
- **新增** `PostImage` Model 與 `Post` 的 `hasMany` 關聯
- **新增** `ThreadsClient` Carousel 發佈方法（`createCarouselItemContainer`、`createCarouselContainer`）
- **修改** `PublishScheduledPost` Job：依圖片數量自動選擇單圖、多圖（Carousel）或純文字發佈流程
- **修改** Filament 後台表單：`FileUpload` 改為多檔上傳，支援圖片排序（Repeater）
- **修改** MCP `CreatePostTool`：`image_url` 改為 `image_urls`（字串陣列），支援多個圖片 URL
- **修改** `PostService::create()` 支援多圖參數與儲存
- **保留** 既有 `posts.image_path` 欄位向後相容，並在 migration 中將既有單圖資料遷移至 `post_images`
- **限制** 圖片數量：最少 0 張（純文字），最多 10 張

## Capabilities

### New Capabilities
- `multi-image-posts`: 多圖片發文功能，包含圖片排序、Carousel API 發佈、後台多圖上傳與排序、MCP 多圖 URL 支援

### Modified Capabilities
- `image-posts`: 既有單圖發文規格需更新——新增多圖支援、圖片排序、Carousel 發佈流程；單圖行為保持不變
- `mcp-server`: `create-post` 工具的 `image_url` 參數改為 `image_urls`（字串陣列），支援傳入多個圖片 URL

## Impact

- **資料庫**：新建 `post_images` 表；`posts.image_path` 保留但標記 deprecated
- **Model**：新增 `PostImage`；`Post` 新增 `images()` 關聯
- **Service**：`PostService`、`ThreadsClient` 新增多圖相關方法
- **Job**：`PublishScheduledPost` 新增 Carousel 發佈分支
- **Filament**：`PostForm`、`PostsTable` 調整多圖 UI
- **MCP**：`CreatePostTool` 參數與 schema 調整
- **Tests**：所有相關測試需更新

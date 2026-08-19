## Why

目前系統只有單一 `User` 角色，所有人登入 `/admin` 後台後擁有相同權限（綁定帳號、發文、回覆）。需要引入 **Admin（管理員）** 與 **User（使用者）** 雙角色分離，讓管理員專注於使用者管理與控管，使用者專注於 Threads 營運操作。

同時，目前貼文僅支援純文字，需要新增 **圖片發文** 功能，讓使用者可以上傳圖片並透過 Threads API 發佈圖文貼文。

## What Changes

### 一、Admin / User 角色分離

- **User（使用者）**：登入 `/user/login`，可綁定 Threads 帳號、排程發文、管理回覆、MCP Token
- **Admin（管理員）**：登入 `/admin/login`，可管理使用者（CRUD）、控管上限、啟用停用、取消綁定/刪除
- 使用雙 Model + 雙 Panel 架構（`User` + `Admin` 獨立表）
- User 新增控管欄位：`max_accounts`、`max_daily_posts`、`max_daily_replies`、`is_active`
- Admin 可強制修改 User 密碼
- Admin 可取消綁定且刪除 Threads 帳號（含 cascade 刪除貼文、回覆）或完整刪除 User
- Artisan 命令：`make:filament-user`（建立使用者，含控管欄位）、`make:filament-admin`（建立管理員）
- 所有 Admin 看到相同資料，不區分權限

### 二、圖片發文功能

- Post 新增 `image_path` 欄位
- 使用 Laravel 預設 `public` disk + Filament `FileUpload` 上傳圖片
- `ThreadsClient` 新增 `createImageContainer()` 方法，支援 `media_type=IMAGE`
- `PublishScheduledPost` 依是否有圖片選擇文字/圖片容器
- MCP `CreatePostTool` 支援圖片參數

### 三、README.md 更新

- 反映 User/Admin 雙角色、登入 URL 變更、圖片發文、管理功能

## Capabilities

### New Capabilities
- `admin-panel`: Admin 管理後台，含 User CRUD、控管設定、取消綁定/刪除
- `image-posts`: 圖片上傳與發佈至 Threads

### Modified Capabilities
- `data-isolation`: Admin 可查看所有 User 資料；User 僅看到自己資料
- `threads-app-management`: 移至 User Panel，Admin 不可綁定
- `mcp-server`: CreatePostTool 新增圖片參數
- `usage-guide`: 更新使用說明

## Impact

- **資料庫**：`admins` 新表、`users` 加 4 欄位、`posts` 加 `image_path`
- **Models**：新增 `Admin`、修改 `User`、修改 `Post`
- **Filament**：新增 `AdminPanelProvider`、修改現有 `AdminPanelProvider` → `UserPanelProvider`、新增 `UserResource`
- **Commands**：新增 `make:filament-admin`
- **Services**：`ThreadsClient` 新增圖片方法、`PostService` 支援圖片
- **Jobs**：`PublishScheduledPost` 支援圖片發佈
- **MCP**：`CreatePostTool` 新增圖片參數
- **Routes**：`ai.php` 調整 MCP 路由

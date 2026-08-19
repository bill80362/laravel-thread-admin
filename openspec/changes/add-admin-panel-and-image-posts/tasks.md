## 1. 資料庫 Migration

- [ ] 1.1 建立 migration：`admins` 表（`id`, `name`, `email` unique, `password`, `remember_token`, `timestamps`）
- [ ] 1.2 建立 migration：`users` 表新增 `max_accounts`（unsignedInteger, default 3）
- [ ] 1.3 建立 migration：`users` 表新增 `max_daily_posts`（unsignedInteger, default 10）
- [ ] 1.4 建立 migration：`users` 表新增 `max_daily_replies`（unsignedInteger, default 50）
- [ ] 1.5 建立 migration：`users` 表新增 `is_active`（boolean, default true）
- [ ] 1.6 建立 migration：`posts` 表新增 `image_path`（nullable string），`text` 改為 nullable
- [ ] 1.7 建立 migration：`threads_accounts.user_id` 加入 `cascadeOnDelete` foreign key（若尚未設定）

## 2. Model 層

- [ ] 2.1 建立 `Admin` Model（`Illuminate\Foundation\Auth\User`，含 `HasFactory`, `Notifiable`）
- [ ] 2.2 建立 `AdminFactory`
- [ ] 2.3 `User` Model：`$fillable` 加入 `max_accounts`, `max_daily_posts`, `max_daily_replies`, `is_active`；casts 加入 `is_active => boolean`
- [ ] 2.4 `Post` Model：`$fillable` 加入 `image_path`；`text` 相關邏輯調整

## 3. Auth 設定

- [ ] 3.1 `config/auth.php`：新增 `admin_web` guard（session, provider=admins）、`admins` provider（eloquent, model=Admin）
- [ ] 3.2 確認 `api` guard 僅使用 `users` provider（Admin 不需要 API）

## 4. Filament Panel Providers

- [ ] 4.1 重命名 `AdminPanelProvider` → `UserPanelProvider`：id=`user`，path=`user`，guard=`web`
- [ ] 4.2 新建 `AdminPanelProvider`：id=`admin`，path=`admin`，guard=`admin_web`
- [ ] 4.3 `UserPanelProvider`：保留現有 Resources（ThreadsAccounts, Posts, Replies, McpTokens）、Pages（Dashboard, EditPassword, UsageGuide）、Widgets（ThreadsOverview）
- [ ] 4.4 `AdminPanelProvider`：Resources（Users）、Pages（Dashboard, EditPassword）、Widgets（AdminOverview）
- [ ] 4.5 `UserPanelProvider` 設為 `->default()`（或依需求調整）

## 5. User Panel Resources（現有 Resource 調整）

- [ ] 5.1 `ThreadsAccountResource`：確認 `getEloquentQuery()` scope 正確（`user_id = auth()->id()`）
- [ ] 5.2 `PostResource`：確認 `getEloquentQuery()` scope 正確
- [ ] 5.3 `ReplyResource`：確認 `getEloquentQuery()` scope 正確
- [ ] 5.4 `McpTokenResource`：確認 `getEloquentQuery()` scope 正確
- [ ] 5.5 `PostForm`：新增 `FileUpload` 欄位（`image`，選填，`public` disk，`posts` 目錄，限制 jpg/png，max 8MB）
- [ ] 5.6 `PostForm`：`text` 欄位改為 nullable（純圖片時可空白）
- [ ] 5.7 `PostsTable`：新增圖片預覽欄位
- [ ] 5.8 `ThreadsOverview` Widget：加上 `user_id` scope

## 6. Admin Panel Resources

- [ ] 6.1 建立 `UserResource`（Filament Resource for Admin 管理 User）
- [ ] 6.2 `UserResource`：List 頁面（表格顯示 name, email, max_accounts, max_daily_posts, max_daily_replies, is_active, created_at）
- [ ] 6.3 `UserResource`：Create 頁面（name, email, password, max_accounts, max_daily_posts, max_daily_replies, is_active）
- [ ] 6.4 `UserResource`：Edit 頁面（name, email, new_password 選填, max_accounts, max_daily_posts, max_daily_replies, is_active）
- [ ] 6.5 `UserResource`：顯示「今日已用/上限」格式（如 `1/3`），需查詢今日 Post/Reply 數量
- [ ] 6.6 `UserResource`：DeleteAction 含確認提示（「確定要刪除此使用者嗎？...此操作無法復原。注意：不會刪除 Threads 上的實際貼文。」）
- [ ] 6.7 `UserResource`：ThreadsAccount 關聯列表（顯示該 User 的帳號，含「取消綁定且刪除」Action）
- [ ] 6.8 取消綁定且刪除 Action：含確認提示（「確定要取消綁定且刪除嗎？...此操作無法復原。注意：不會刪除 Threads 上的實際貼文。」），刪除 ThreadsAccount（cascade Post/Reply）
- [ ] 6.9 建立 `AdminOverview` Widget（使用者總數、啟用數、停用數）

## 7. Artisan 命令

- [ ] 7.1 修改 `make:filament-user` 命令：新增控管欄位輸入（`max_accounts` 預設 3、`max_daily_posts` 預設 10、`max_daily_replies` 預設 50、`is_active` 預設 true）
- [ ] 7.2 建立 `make:filament-admin` 命令（類似 `make:filament-user`，寫入 `admins` 表，無控管欄位）

## 8. 圖片發文功能

- [ ] 8.1 `ThreadsClient`：新增 `createImageContainer(ThreadsAccount $account, string $imageUrl, ?string $text = null): string`
- [ ] 8.2 `PostService::create()`：支援 `image` 檔案上傳，儲存至 `public` disk，寫入 `image_path`
- [ ] 8.3 `PostService::create()`：驗證至少要有 `text` 或 `image` 其中之一
- [ ] 8.4 `PublishScheduledPost`：判斷 `image_path` 是否存在，選擇呼叫 `createImageContainer` 或 `createTextContainer`
- [ ] 8.5 `CreatePost`（Filament Page）：`mutateFormDataBeforeCreate()` 處理圖片上傳
- [ ] 8.6 `EditPost`（Filament Page）：支援圖片欄位顯示與修改

## 9. MCP Tools

- [ ] 9.1 `CreatePostTool`：新增選填參數 `image_url`（string, url format）
- [ ] 9.2 `CreatePostTool::handle()`：若有 `image_url` 則呼叫 `createImageContainer`，否則 `createTextContainer`
- [ ] 9.3 `CreatePostTool::schema()`：新增 `image_url` schema 定義
- [ ] 9.4 `routes/ai.php`：確認 MCP 路由不受 Panel 變更影響（MCP 走 `auth:api`，獨立於 Panel）

## 10. 使用說明更新

- [ ] 10.1 `UsageGuide` 頁面內容更新：反映 User/Admin 雙角色、登入 URL 變更、圖片發文功能

## 11. README.md 更新

- [ ] 11.1 更新架構說明（User vs Admin 雙角色）
- [ ] 11.2 更新登入 URL（`/user/login` 和 `/admin/login`）
- [ ] 11.3 新增圖片發文功能說明
- [ ] 11.4 新增管理員功能說明
- [ ] 11.5 更新安裝步驟（`make:filament-user` + `make:filament-admin`）

## 12. 驗證

- [ ] 12.1 執行 `php artisan migrate:fresh` 確認 migration 成功
- [ ] 12.2 執行 `php artisan make:filament-user` 建立使用者
- [ ] 12.3 執行 `php artisan make:filament-admin` 建立管理員
- [ ] 12.4 手動測試：Admin 登入 `/admin/login` → 管理 User
- [ ] 12.5 手動測試：User 登入 `/user/login` → 綁定帳號、發文、回覆
- [ ] 12.6 手動測試：圖片上傳與發佈流程
- [ ] 12.7 手動測試：Admin 取消綁定 Threads 帳號（cascade 刪除）
- [ ] 12.8 手動測試：Admin 完整刪除 User（cascade 刪除）
- [ ] 12.9 執行 `php artisan test --compact` 確認現有測試通過
- [ ] 12.10 執行 `vendor/bin/pint --format agent` 修正程式碼風格

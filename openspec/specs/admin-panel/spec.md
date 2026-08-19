# Admin Panel

Admin（管理員）管理後台，提供使用者管理、控管設定、取消綁定與刪除功能。

## 雙角色雙 Panel 架構

### Admin Model

- `admins` 表欄位：`id`, `name`, `email`（unique）, `password`（hashed）, `remember_token`, `timestamps`
- Admin 使用 `Illuminate\Foundation\Auth\User` 基底，含 `HasFactory`, `Notifiable`
- Admin **不**使用 `HasApiTokens`（不需要 Passport / MCP）

### Auth Guards

- `web` guard：session driver，provider=`users`（User 登入 `/user/login`）
- `admin_web` guard：session driver，provider=`admins`（Admin 登入 `/admin/login`）
- `api` guard：passport driver，provider=`users`（MCP API，僅 User 可用）

### Filament Panels

| Panel | ID | Path | Guard | 使用者 |
|-------|-----|------|-------|--------|
| UserPanelProvider | `user` | `/user` | `web` | User |
| AdminPanelProvider | `admin` | `/admin` | `admin_web` | Admin |

### User Panel Resources

- ThreadsAccounts（Threads 帳號管理）
- Posts（排程發文）
- Replies（回覆面板）
- McpTokens（MCP 控管）
- Pages：Dashboard, EditPassword, UsageGuide
- Widgets：ThreadsOverview

### Admin Panel Resources

- Users（使用者管理 CRUD）
- Pages：Dashboard, EditPassword
- Widgets：AdminOverview

## User 控管欄位

### 資料庫欄位（users 表新增）

| 欄位 | 型別 | 預設值 | 說明 |
|------|------|--------|------|
| `max_accounts` | unsignedInteger | 3 | 最大綁定 Threads 帳號數 |
| `max_daily_posts` | unsignedInteger | 10 | 每日發文上限 |
| `max_daily_replies` | unsignedInteger | 50 | 每日回覆上限 |
| `is_active` | boolean | true | 帳號啟用/停用 |

### 顯示格式

- 在 Admin 的 User 列表中，控管欄位顯示為 `今日已用數量 / 上限`
- 例如：`1/3`（已綁定 1 個帳號，上限 3 個）
- 今日數量需即時查詢：
  - 綁定帳號數：`ThreadsAccount::where('user_id', $userId)->count()`
  - 今日發文數：`Post::where('user_id', $userId)->whereDate('created_at', today())->count()`
  - 今日回覆數：`Reply::where('user_id', $userId)->whereDate('created_at', today())->count()`

### is_active 行為

- `is_active = false` 時，User 無法登入
- 排程中的貼文不會被 `PublishScheduledPost` 處理（Job 中檢查 `is_active`）
- Admin 可隨時切換啟用/停用

## Admin 管理功能

### User CRUD

- **List**：表格顯示 name, email, 綁定帳號數（今日/上限）, 今日發文數（今日/上限）, 今日回覆數（今日/上限）, is_active, created_at
- **Create**：name（必填）, email（必填，unique）, password（必填）, max_accounts（預設 3）, max_daily_posts（預設 10）, max_daily_replies（預設 50）, is_active（預設 true）
- **Edit**：name, email, new_password（選填，留空不修改）, max_accounts, max_daily_posts, max_daily_replies, is_active
- **Delete**：完整刪除 User 及其所有關聯資料（見下方）

### 取消綁定且刪除 Threads 帳號

- 在 UserResource 的關聯 ThreadsAccount 列表中提供「取消綁定且刪除」Action
- 點擊後彈出確認提示：「確定要取消綁定且刪除嗎？該帳號下的所有貼文與回覆記錄將一併刪除，此操作無法復原。注意：不會刪除 Threads 上的實際貼文。」
- 確認後刪除 ThreadsAccount（cascade 刪除 Post、Reply）
- **不**刪除 Threads 上的實際貼文/回覆

### 完整刪除 User

- 在 UserResource 的 List 頁面提供 DeleteAction
- 點擊後彈出確認提示：「確定要刪除此使用者嗎？該使用者下的所有帳號、貼文、回覆與 MCP Token 將一併刪除，此操作無法復原。注意：不會刪除 Threads 上的實際貼文。」
- 確認後刪除 User（cascade 刪除 ThreadsAccount → Post → Reply）
- 同時刪除該 User 的所有 Passport Token
- **不**刪除 Threads 上的實際貼文/回覆

### 資料庫 Cascade 設定

- `threads_accounts.user_id` → `foreignId()->constrained()->cascadeOnDelete()`
- `posts.threads_account_id` → `foreignId()->constrained()->cascadeOnDelete()`（已存在）
- `replies.threads_account_id` → `foreignId()->constrained()->cascadeOnDelete()`（已存在）

## Artisan 命令

### make:filament-user

- 現有命令，建立 User
- `php artisan make:filament-user`

### make:filament-admin

- 新建命令，建立 Admin
- `php artisan make:filament-admin`
- 互動式輸入：name, email, password
- 寫入 `admins` 表

## Admin 權限

- 所有 Admin 登入後看到相同資料，不區分權限
- Admin 管理的 User 是共通的（所有 Admin 看到相同的 User 列表）
- Admin 不能綁定 Threads 帳號、不能發文、不能回覆

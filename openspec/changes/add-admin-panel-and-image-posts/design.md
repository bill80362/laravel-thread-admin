## Context

目前系統為單一 `User` + 單一 Filament Panel（id=`admin`，路徑=`/admin`）。需要拆分為雙角色雙 Panel。

現有關聯鏈：`User → ThreadsAccount → Post/Reply`。Admin 不參與此鏈，僅管理 User。

## Goals / Non-Goals

**Goals:**
- 雙 Model（`User` + `Admin`）雙 Panel（`user` + `admin`）架構
- User Panel（`/user`）：Threads 帳號、排程發文、回覆面板、MCP 控管、使用說明
- Admin Panel（`/admin`）：使用者管理（CRUD）、控管設定、取消綁定/刪除
- User 控管欄位：`max_accounts`、`max_daily_posts`、`max_daily_replies`、`is_active`
- 圖片發文：上傳 → 公開 URL → Threads API Image Container → Publish
- Artisan 命令：`make:filament-user`、`make:filament-admin`

**Non-Goals:**
- Admin 不綁定 Threads 帳號、不發文、不回覆
- Admin 之間不區分權限（所有 Admin 看到相同資料）
- 不刪除 Threads 上的實際貼文/回覆（僅刪除本地資料庫記錄）
- 不改變 OAuth 流程的整體架構

## Decisions

### Decision 1: 雙 Model + 雙 Panel（方案 A）

**選擇**：`User` 和 `Admin` 為兩個獨立的 Eloquent Model 和資料庫表，各自對應一個 Filament Panel。

```
┌──────────────────────────────────────────────────┐
│                  Filament Panels                   │
│                                                    │
│  ┌─────────────────────┐  ┌─────────────────────┐ │
│  │  User Panel (user)   │  │ Admin Panel (admin)  │ │
│  │  path: /user         │  │ path: /admin         │ │
│  │  guard: web          │  │ guard: admin_web     │ │
│  │                     │  │                     │ │
│  │  Resources:         │  │  Resources:         │ │
│  │  - ThreadsAccounts  │  │  - Users            │ │
│  │  - Posts            │  │                     │ │
│  │  - Replies          │  │  Pages:             │ │
│  │  - McpTokens        │  │  - Dashboard        │ │
│  │                     │  │  - EditPassword     │ │
│  │  Pages:             │  │                     │ │
│  │  - Dashboard        │  │                     │ │
│  │  - EditPassword     │  │                     │ │
│  │  - UsageGuide       │  │                     │ │
│  └─────────────────────┘  └─────────────────────┘ │
│                                                    │
│  ┌──────────────────┐  ┌──────────────────────┐   │
│  │  users 表         │  │  admins 表            │   │
│  │  - id, name, ... │  │  - id, name, email,  │   │
│  │  - max_accounts  │  │    password           │   │
│  │  - max_daily_*   │  │                      │   │
│  │  - is_active     │  │  (無業務欄位)         │   │
│  │  - HasApiTokens  │  │                      │   │
│  └──────────────────┘  └──────────────────────┘   │
└──────────────────────────────────────────────────┘
```

**理由**：
- User 和 Admin 職責完全不同，不應混在同一張表
- User 需要 `HasApiTokens`（Passport），Admin 不需要
- Admin 不需要 `max_accounts` 等業務欄位
- 兩個 Panel 的 Resource 完全不同，不會混淆
- Filament 原生支援多 Panel，實作清晰

### Decision 2: Panel ID 與路徑命名

**選擇**：
- User Panel：`id='user'`，`path='user'`，guard=`web`
- Admin Panel：`id='admin'`，`path='admin'`，guard=`admin_web`

**auth.php guards**：
```php
'guards' => [
    'web' => ['driver' => 'session', 'provider' => 'users'],
    'admin_web' => ['driver' => 'session', 'provider' => 'admins'],
    'api' => ['driver' => 'passport', 'provider' => 'users'],
],
'providers' => [
    'users' => ['driver' => 'eloquent', 'model' => User::class],
    'admins' => ['driver' => 'eloquent', 'model' => Admin::class],
],
```

### Decision 3: User 控管欄位設計

**選擇**：在 `users` 表新增以下欄位：

| 欄位 | 型別 | 預設值 | 說明 |
|------|------|--------|------|
| `max_accounts` | `unsignedInteger` | `3` | 最大綁定 Threads 帳號數 |
| `max_daily_posts` | `unsignedInteger` | `10` | 每日發文上限 |
| `max_daily_replies` | `unsignedInteger` | `50` | 每日回覆上限 |
| `is_active` | `boolean` | `true` | 帳號啟用/停用 |

**顯示格式**：`1/3`（今日已用數量 / 上限）

**理由**：
- 每個 User 獨立設定，Admin 可個別調整
- 預設值提供合理的初始限制
- `is_active` 停用時，User 無法登入，排程中的貼文也不會發佈

### Decision 4: Admin 刪除 User 的範圍

**選擇**：Admin 可執行兩種刪除操作。**不使用資料庫 FK**，cascade 刪除在 Model 層處理。

**A) 取消綁定且刪除 Threads 帳號**（在 ThreadsAccount 列表）：
- 刪除該 ThreadsAccount
- 手動 cascade 刪除該帳號下的所有 Post 和 Reply（Model 層 `deleting` 事件或 Service 方法）
- 不刪除 Threads 上的實際貼文/回覆
- 彈出確認提示：「確定要取消綁定且刪除嗎？該帳號下的所有貼文與回覆記錄將一併刪除，此操作無法復原。注意：不會刪除 Threads 上的實際貼文。」

**B) 完整刪除 User**（在 User 列表）：
- 刪除該 User
- 手動 cascade 刪除其所有 ThreadsAccount → Post → Reply
- 刪除其 MCP Token（Passport Token）
- 不刪除 Threads 上的實際貼文/回覆
- 彈出確認提示：「確定要刪除此使用者嗎？該使用者下的所有帳號、貼文、回覆與 MCP Token 將一併刪除，此操作無法復原。注意：不會刪除 Threads 上的實際貼文。」

**實作方式**：
- 使用 Filament `DeleteAction` + `->requiresConfirmation()`
- `->modalDescription()` 顯示警告文字
- 資料庫層級：**不使用 FK**，cascade 刪除在 Model `booted()` 的 `deleting` 事件中處理

### Decision 5: Admin 修改 User 密碼

**選擇**：在 UserResource 的 Edit 頁面提供「修改密碼」欄位。

- 使用 `TextInput::make('new_password')->password()`，非必填
- 若填寫則在 `mutateFormDataBeforeSave()` 中 `Hash::make()` 後寫入
- 若未填寫則保留原密碼

### Decision 6: 圖片發文流程

**選擇**：使用 Laravel `public` disk + Filament `FileUpload`。

```
┌─────────────────────────────────────────────────────┐
│              圖片發文流程                              │
│                                                     │
│  1. Filament FileUpload → storage/app/public/posts/ │
│                    ↓                                │
│  2. Storage::disk('public')->url($path)             │
│     → http://domain/storage/posts/xxx.jpg           │
│                    ↓                                │
│  3. ThreadsClient::createImageContainer()           │
│     POST /{user-id}/threads                         │
│     media_type=IMAGE                                │
│     image_url=<公開URL>                              │
│     text=<選填文字>                                  │
│                    ↓                                │
│  4. 等待 30 秒 → publishContainer()                  │
│                                                     │
└─────────────────────────────────────────────────────┘
```

**Threads API 圖片規則**：
- 圖片格式：JPEG、PNG
- 最大檔案大小：8 MB
- 圖片 URL 必須公開可訪問
- 文字為選填（可純圖片或圖片+文字）
- 圖片與文字不可分開發佈（同一 container）

**Post 表變更**：
- 新增 `image_path` 欄位（nullable string）
- `text` 改為 nullable（純圖片時無文字）

**PublishScheduledPost 邏輯**：
```php
if ($post->image_path !== null) {
    $imageUrl = Storage::disk('public')->url($post->image_path);
    $creationId = $threads->createImageContainer($account, $imageUrl, $post->text);
} else {
    $creationId = $threads->createTextContainer($account, $post->text);
}
```

### Decision 7: MCP CreatePostTool 圖片支援

**選擇**：`CreatePostTool` 新增選填參數 `image_url`。

- MCP 客戶端需自行上傳圖片到公開 URL，再傳入 `image_url`
- 不透過 MCP 上傳圖片（MCP 不適合傳輸二進位檔案）
- 若有 `image_url` 則建立 Image Container，否則建立 Text Container

### Decision 8: Artisan 命令

**選擇**：
- `make:filament-user`：修改現有命令，新增控管欄位輸入（`max_accounts` 預設 3、`max_daily_posts` 預設 10、`max_daily_replies` 預設 50、`is_active` 預設 true）
- `make:filament-admin`：新建命令，建立 Admin（`php artisan make:filament-admin`），僅輸入 name、email、password

Admin 命令與 User 命令結構相同，但寫入 `admins` 表且無控管欄位。

### Decision 9: User Panel 的 Dashboard Widget

**選擇**：`ThreadsOverview` Widget 移至 User Panel，加上 `user_id` scope。

Admin Panel 的 Dashboard 使用不同的 Widget（如：使用者總數、啟用/停用統計）。

### Decision 10: 現有 Panel Provider 重命名

**選擇**：
- `AdminPanelProvider` → `UserPanelProvider`（id=`user`，path=`user`）
- 新建 `AdminPanelProvider`（id=`admin`，path=`admin`）

## Risks / Trade-offs

- **Migration 順序**：`posts.text` 改 nullable 需在圖片功能 migration 中處理
- **storage:link**：需確保 `php artisan storage:link` 已執行，否則圖片 URL 無法訪問
- **APP_URL**：圖片 URL 依賴 `APP_URL` 設定正確，本地開發需注意
- **Passport**：Admin 不需要 Passport，`Laravel\Passport\HasApiTokens` 僅在 User 使用
- **Filament Shield**：目前專案已安裝 `bezhansalleh/filament-shield`，需確認是否影響雙 Panel 設定

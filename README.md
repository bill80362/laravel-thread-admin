# Threads 社群營運平台

一站式管理多個 Threads 帳號的營運後台，支援 OAuth 綁定、排程發文（含圖片）、集中回覆收集與快速回覆。

## 角色架構

| 角色 | 登入網址 | 功能 |
|------|----------|------|
| **Admin（管理員）** | `/admin/login` | 管理使用者（CRUD）、控管上限、啟用/停用、取消綁定且刪除 |
| **User（使用者）** | `/user/login` | 綁定 Threads 帳號、排程發文（含圖片）、回覆管理、MCP Token |

- Admin 之間不區分權限，所有 Admin 看到相同資料
- Admin 不能綁定 Threads 帳號、不能發文、不能回覆

## Roadmap

| 階段 | 目標 | 狀態 |
|------|------|------|
| **初期 MVP** | 多帳號集中管理 → 文章定時排程發送（含圖片）→ 集中收文章回覆 → 快速回覆 | 🚧 開發中 |
| **中期** | AI 產文 → 人工審核 → 快速發出 | 📋 規劃中 |
| **後期** | AI 自動產文回文 | 📋 規劃中 |

## 技術棧

- **PHP 8.4** + **Laravel 13**
- **Filament 5** — 管理後台 UI（雙 Panel：User + Admin）
- **SQLite** — 資料庫（可替換為 MySQL/PostgreSQL）
- **Guzzle** — Threads Graph API 呼叫
- **Laravel Queue (database)** — 排程發文與回覆收集
- **Laravel Passport** — MCP API OAuth 認證
- **Spatie Laravel Permission + Filament Shield** — 權限管理（預留）

## 安裝步驟

```bash
# 1. 複製專案
git clone <repo-url>
cd laravel-thread-admin

# 2. 安裝相依套件
composer install
npm install && npm run build

# 3. 環境設定
cp .env.example .env
php artisan key:generate

# 4. 執行 migration
php artisan migrate

# 5. 建立 storage link（圖片發文需要）
php artisan storage:link

# 6. 建立管理員帳號
php artisan make:filament-admin

# 7. 建立使用者帳號
php artisan make:filament-user
```

## Meta App 前置設定

使用 Threads API 前，需先在 [Meta for Developers](https://developers.facebook.com/) 建立 App：

1. 建立 Meta App，選擇 **Threads** use case
2. 記下 **Threads App ID** 與 **App Secret**（與 Facebook App ID 不同）
3. 在 App Dashboard → **App roles** → **Roles** 新增 **Threads Tester**
4. 設定 OAuth redirect URI：`https://你的網域/threads/oauth/callback`
5. 設定 Webhook 回呼網址：`https://你的網域/threads/webhook`

> **注意**：`redirect_uri` 由 `APP_URL` 自動推導（`{APP_URL}/threads/oauth/callback`），無需在 `.env` 額外設定。

### 回呼網址一覽

| 用途 | 網址 |
|------|------|
| OAuth 綁定回呼（Callback） | `{APP_URL}/threads/oauth/callback` |
| Webhook 事件回呼 | `{APP_URL}/threads/webhook` |

> 兩者皆由 `APP_URL` 自動推導，無需在 `.env` 額外設定。

### 需要的權限

| 權限 | 用途 |
|------|------|
| `threads_basic` | 讀取個人資料、發文 |
| `threads_content_publish` | 發佈貼文 |
| `threads_manage_replies` | 管理回覆 |
| `threads_read_replies` | 讀取回覆 |

> **注意**：測試階段使用 Threads Tester 即可。正式上線需通過 **App Review** 取得 Advanced Access。

## 環境變數設定

在 `.env` 中設定 Threads API 憑證：

```env
THREADS_CLIENT_ID=你的Threads_App_ID
THREADS_CLIENT_SECRET=你的Threads_App_Secret
```

> `client_id` 與 `client_secret` 來自 Meta App 的 Threads use case 設定頁面。`client_secret` 不會直接出現在程式碼或資料庫中，僅透過 `.env` 管理。

## OAuth 綁定流程

```
使用者在 Threads 帳號頁面點擊「綁定帳號」
       │
       ▼
系統產生 OAuth state（存 DB，含 user_id 與過期時間）
       │
       ▼
跳轉 Threads 授權頁面（使用 .env 中的 client_id）
       │
       ▼
使用者同意授權 → 回調取得授權碼 + state
       │
       ▼
系統解析 state → 取得 user_id → 用 .env 憑證交換 token
       │
       ▼
短效 token (1hr) → 長效 token (60天) → 存入 threads_accounts
       │
       ▼
每日自動檢查，到期前 7 天自動續命
續命失敗 → 標記「需重新授權」→ 可點擊「重新授權」更新
```

## 排程與 Queue 設定

### crontab（每分鐘觸發排程）

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Queue Worker（處理發文與回覆收集 Job）

```bash
php artisan queue:work --tries=3
```

生產環境建議使用 Supervisor 管理 Queue Worker。

## 操作說明

### 1. 設定環境變數

在 `.env` 中設定 `THREADS_CLIENT_ID` 與 `THREADS_CLIENT_SECRET`（來自 Meta App 的 Threads use case 設定頁面）。

### 2. 建立帳號

```bash
# 建立管理員（登入 /admin）
php artisan make:filament-admin

# 建立使用者（登入 /user）
php artisan make:filament-user
```

### 3. 管理員功能（Admin Panel：`/admin`）

- **使用者管理**：新增、編輯、刪除使用者
- **控管設定**：設定每位使用者的最大綁定帳號數、每日發文上限、每日回覆上限
- **啟用/停用**：停用的使用者無法登入，排程貼文不會發佈
- **取消綁定且刪除**：刪除使用者的 Threads 帳號及其所有貼文、回覆記錄（不刪除 Threads 上的實際貼文）
- **完整刪除使用者**：刪除使用者及其所有帳號、貼文、回覆、MCP Token（不刪除 Threads 上的實際貼文）

### 4. 使用者功能（User Panel：`/user`）

#### 綁定 Threads 帳號

進入 **Threads 帳號** 頁面 → 點擊「綁定 Threads 帳號」→ 在 Threads 授權頁同意 → 自動導回後台。

#### 重新授權

當帳號 token 失效（狀態顯示「需重新授權」），在 **Threads 帳號** 頁面點擊「重新授權」即可更新 token，不需先解除綁定。

#### 排程發文（含圖片）

進入 **排程發文** 頁面 → 點擊「新增」→ 選擇目標帳號、輸入貼文內容（≤500 字）或上傳圖片（JPEG/PNG，最大 8MB）、設定發佈時間 → 儲存。

> 文字與圖片至少需填寫一項。可純文字、純圖片、或圖文混合發佈。

系統每分鐘自動檢查到期貼文並發佈。發文流程：
1. 上傳圖片至伺服器（如有）
2. 建立 media container（文字或圖片）
3. 等待約 30 秒
4. 發佈至 Threads

#### 查看回覆

進入 **回覆面板** → 系統每 5 分鐘自動拉取各帳號最新回覆 → 依帳號/狀態篩選。

#### 快速回覆

在回覆面板點擊「回覆」→ 輸入內容 → 送出。或點擊「忽略」標記不需處理的回覆。

#### MCP 服務設定

進入 **MCP 控管** 頁面 → 建立 Token → 設定至 ChatGPT 或 Claude Desktop 即可透過 AI 工具操作發文與回覆。

### 5. Dashboard

**User Panel** 首頁顯示：
- 已綁定帳號數
- 待回覆留言數
- 需重新授權帳號數

**Admin Panel** 首頁顯示：
- 使用者總數
- 啟用中數量
- 已停用數量

## 開發

```bash
# 啟動開發伺服器
composer run dev

# 執行測試
php artisan test --compact

# 程式碼格式化
vendor/bin/pint
```

## License

MIT

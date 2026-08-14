# Threads 社群營運平台

一站式管理多個 Threads 帳號的營運後台，支援 OAuth 綁定、排程發文、集中回覆收集與快速回覆。

## Roadmap

| 階段 | 目標 | 狀態 |
|------|------|------|
| **初期 MVP** | 多帳號集中管理 → 文章定時排程發送 → 集中收文章回覆 → 快速回覆 | 🚧 開發中 |
| **中期** | AI 產文 → 人工審核 → 快速發出 | 📋 規劃中 |
| **後期** | AI 自動產文回文 | 📋 規劃中 |

## 技術棧

- **PHP 8.4** + **Laravel 13**
- **Filament 5** — 管理後台 UI
- **SQLite** — 資料庫（可替換為 MySQL/PostgreSQL）
- **Guzzle** — Threads Graph API 呼叫
- **Laravel Queue (database)** — 排程發文與回覆收集
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

# 5. 建立管理員帳號（Filament 後台登入用）
php artisan make:filament-user
```

## Meta App 前置設定

使用 Threads API 前，需先在 [Meta for Developers](https://developers.facebook.com/) 建立 App：

1. 建立 Meta App，選擇 **Threads** use case
2. 記下 **Threads App ID** 與 **App Secret**（與 Facebook App ID 不同）
3. 在 App Dashboard → **App roles** → **Roles** 新增 **Threads Tester**
4. 設定 OAuth redirect URI：`https://你的網域/threads/oauth/callback`
5. 設定 `.env`：

```env
THREADS_CLIENT_ID=你的Threads_App_ID
THREADS_CLIENT_SECRET=你的App_Secret
THREADS_REDIRECT_URI=https://你的網域/threads/oauth/callback
```

### 需要的權限

| 權限 | 用途 |
|------|------|
| `threads_basic` | 讀取個人資料、發文 |
| `threads_content_publish` | 發佈貼文 |
| `threads_manage_replies` | 管理回覆 |
| `threads_read_replies` | 讀取回覆 |

> **注意**：測試階段使用 Threads Tester 即可。正式上線需通過 **App Review** 取得 Advanced Access。

## OAuth 綁定流程

```
管理員點擊「綁定帳號」
       │
       ▼
跳轉 Threads 授權頁面
       │
       ▼
使用者同意授權 → 回調取得授權碼 (1hr 有效)
       │
       ▼
系統交換短效 token (1hr) → 再交換長效 token (60天)
       │
       ▼
每日自動檢查，到期前 7 天自動續命
續命失敗 → 標記「需重新授權」→ Dashboard 顯示警告
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

### 1. 綁定 Threads 帳號

進入 **Threads 帳號** 頁面 → 點擊「綁定 Threads 帳號」→ 在 Threads 授權頁同意 → 自動導回後台。

### 2. 排程發文

進入 **排程發文** 頁面 → 點擊「新增」→ 選擇目標帳號、輸入貼文內容（≤500 字）、設定發佈時間 → 儲存。

系統每分鐘自動檢查到期貼文並發佈。發文流程：
1. 建立 media container
2. 等待約 30 秒
3. 發佈至 Threads

### 3. 查看回覆

進入 **回覆面板** → 系統每 5 分鐘自動拉取各帳號最新回覆 → 依帳號/狀態篩選。

### 4. 快速回覆

在回覆面板點擊「回覆」→ 輸入內容 → 送出。或點擊「忽略」標記不需處理的回覆。

### 5. Dashboard

首頁顯示：
- 已綁定帳號數
- 待回覆留言數
- 需重新授權帳號數

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

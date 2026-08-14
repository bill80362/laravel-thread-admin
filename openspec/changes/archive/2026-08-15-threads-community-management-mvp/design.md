## Context

專案目前是 Laravel 13（PHP 8.4）+ Filament 5 骨架，已安裝 `filament/filament`、`bezhansalleh/filament-shield`、`spatie/laravel-permission`、`bezhansalleh/filament-language-switch`，但尚未執行 `filament:install --panels`（`app/Providers` 只有 `AppServiceProvider`，無 Panel Provider）。資料庫使用 SQLite，Queue 與 Cache 已設定為 `database` driver。動機與需求範圍見 proposal.md。

外部約束（來自 Threads API 官方文件）：
- 發文即使是純文字也需兩階段：`POST /{user_id}/threads`（建 media container）→ 等待約 30 秒 → `POST /{user_id}/threads_publish`（發佈）。
- Token 生命週期：授權碼（1hr）→ 短效 token（1hr）→ 長效 token（60 天），續命用 `GET /refresh_access_token`；public profile 授權 90 天有效、private profile 無法續命需重新授權。
- 發文 rate limit：24 小時滾動窗口 250 篇；回覆 1,000 篇。
- 純文字貼文上限 500 字元。
- 需 App Review（Advanced Access）才可使用 Webhook，MVP 因此走 Polling。

## Goals / Non-Goals

**Goals:**
- 以最小依賴、最小程式量建立可用的單一管理員營運後台。
- 資料模型預留 webhook 升級空間（`source` 欄位），避免未來重構。
- 排程發文的兩階段流程用 Queue Job 分段處理，確保 API 的 30 秒等待不阻塞請求。

**Non-Goals:**
- 不實作 Webhook（中期再考慮）。
- 不實作圖片/影片貼文（MVP 僅純文字）。
- 不引入 `league/oauth2-client`（詳見 Decisions）。
- 不做多營運人員的複雜 RBAC；MVP 僅保護後台登入，Shield 保持現狀、暫不建 role 權限模型。

## Decisions

### 1. OAuth/API 用 Guzzle 手寫輕量 `ThreadsClient`
- **理由**：Threads 是 Facebook 的變體 OAuth 流程，`league/oauth2-client` 無維護中的 Threads provider，導入後仍需自寫 provider，等於「先套抽象再填實作」。整個 API 面約 9 個 HTTP 方法，Guzzle 直接寫最透明、易維護。
- **替代方案**：`league/oauth2-client`（需自寫 provider，複雜度更高，換平台才有優勢）。

### 2. 回覆收集用 Polling（排程定時拉取）
- **理由**：Webhook 需 App Review（Advanced Access）+ 公開伺服器 + 驗證企業，MVP 門檻過高。Polling 只需 token，分鐘級延遲可接受。
- **資料模型預留**：`replies.source` 欄位記錄 `polling`/`webhook`，未來升級不需遷移結構。
- **替代方案**：Webhook（中期再評估）。

### 3. 排程發文用 Laravel Scheduler + Queue（database）
- **理由**：Scheduler 每分鐘觸發，撈出到期貼文後派發 Queue Job；Job 內執行兩階段發文並用 `delay()` 處理 30 秒等待。無需額外套件。
- **分段流程**：
  1. `PublishScheduledPost` Job 建立 media container，取得 `creation_id`。
  2. 使用 `->delay(now()->addSeconds(30))` 重新派發同一 Job（狀態 `publishing`）檢查 container 狀態並發佈。
  3. 成功 → `published`；失敗 → `failed` + `error_message`（並依錯誤判斷是否標記帳號 `needs_reauth`）。

### 4. 資料模型
三張核心表，`threads_accounts` 的 token 使用 Laravel 內建 `encrypted` cast：

```
threads_accounts
 ├─ id
 ├─ threads_user_id        (string, unique)
 ├─ username               (string)
 ├─ name / avatar          (nullable)
 ├─ access_token           (encrypted)
 ├─ token_expires_at       (datetime)
 ├─ status                 (enum: active / needs_reauth / disabled)
 └─ last_synced_at         (datetime, nullable)

posts
 ├─ id
 ├─ threads_account_id     (FK)
 ├─ threads_media_id       (nullable)   ← 發佈成功後回填
 ├─ text                   (string, ≤500)
 ├─ scheduled_at           (datetime)
 ├─ published_at           (datetime, nullable)
 ├─ status                 (enum: draft / scheduled / publishing / published / failed)
 └─ error_message          (nullable)

replies
 ├─ id
 ├─ threads_account_id     (FK)
 ├─ post_id                (FK, nullable)
 ├─ threads_reply_id       (string, unique)  ← 去重
 ├─ author_username        (string)
 ├─ text                   (string)
 ├─ source                 (enum: polling / webhook)   ← 預留
 ├─ status                 (enum: new / replied / ignored)
 └─ replied_at             (datetime, nullable)
```

### 5. Filament 結構
- 單一 Panel（Admin），`App\Filament` 命名空間，登入保護用 Filament 內建 auth（`User` model）。
- Resource：
  - `ThreadsAccountResource`（帳號管理，含綁定/解除綁定 Action）
  - `PostResource`（排程發文）
  - `ReplyResource`（回覆面板，唯讀列表 + 快速回覆/忽略 Action）
- Dashboard Widget：帳號狀態概覽（token 到期警訊、待處理回覆數）。
- 透過 `ThreadsClient` 封裝所有 API 呼叫，Resource/Action 不直接碰 Guzzle。

### 6. token 續命 Job
- `RefreshThreadsTokens` Job 每日執行，對 `token_expires_at ≤ now+7天` 的帳號呼叫 `GET /refresh_access_token`；成功更新 token 與到期日，失敗標記 `needs_reauth`。

### 7. 設定檔
- `config/services.php` 新增 `threads` 區塊：`client_id`、`client_secret`、`redirect_uri`。
- `.env.example` 新增 `THREADS_CLIENT_ID`、`THREADS_CLIENT_SECRET`、`THREADS_REDIRECT_URI`。
- `bootstrap/app.php` 用 `withSchedule()` 註冊排程；`routes/console.php` 定義命令觸發。

## Risks / Trade-offs

- **Polling 延遲**：回覆最慢約 5 分鐘才出現在面板 → Mitigation：預設 5 分鐘，可透過 config 調整；未來升級 webhook。
- **兩階段發文的 30 秒等待**：若 Job 在等待期間被中斷，貼文會停留在 `publishing` → Mitigation：Scheduler 每次也重試 `publishing` 狀態且逾時未完成的貼文；記錄 container `creation_id` 以續接。
- **token 續命失敗（private profile）**：Threads 明定 private profile 無法續命 → Mitigation：標記 `needs_reauth` 並在 dashboard 醒目提示，管理員手動重新授權。
- **rate limit（250/日）**：大量排程可能撞上限 → Mitigation：Job 失敗時記錄錯誤，dashboard 顯示；未來可讀取 `threads_publishing_limit` 做前置檢查。
- **SQLite 併發限制**：單一管理員 MVP 可接受 → Mitigation：Queue 用 database driver 串行處理即可，規模化再遷移。

## Migration Plan

- 全新功能，無既有資料需遷移；僅新增 migration 三張表。
- 部署步驟：`composer install` → `php artisan filament:install --panels` → `php artisan migrate` → 設定 `.env` THREADS_* → 設定 crontab `* * * * * php artisan schedule:run` → 啟動 Queue worker。
- Rollback：`php artisan migrate:rollback` 移除三張表；移除 `config/services.php` threads 區塊與排程註冊即可。

## Open Questions

- 無（架構與範圍均已收斂，任務拆解見 tasks.md）。

## Why

目前系統僅透過定期輪詢（Polling）抓取 Threads 回覆，回覆並非即時更新，管理者需等待下一個同步週期才能看到新回覆。Threads 提供 Webhook 即時事件通知，可讓系統在回覆產生時立即收到通知並建立回覆記錄，提升回覆收集的即時性與效率。

## What Changes

- 新增 Threads Webhook 接收端點，處理 Meta 的 Webhook 驗證（`hub.mode` / `hub.verify_token` / `hub.challenge`）與事件推送。
- 新增 Webhook 驗證 token 的設定（`THREADS_WEBHOOK_VERIFY_TOKEN`），並以環境變數管理。
- 收到回覆相關事件時，將新回覆寫入 `replies` 表，來源標記為 `webhook`（`ReplySource::Webhook`）。
- 新增 Webhook 事件處理的共用 Service，將業務邏輯收斂至 `app/Services/`。
- 更新使用說明頁面，說明 Webhook 的設定方式與回呼網址。

## Capabilities

### New Capabilities
- `threads-webhook`: 接收 Threads Webhook 驗證與事件推送，將回覆事件即時寫入系統。

### Modified Capabilities
- `replies-sync-notice`: 回覆同步機制說明需從「僅輪詢」擴充為「輪詢 + Webhook 即時通知」。
- `threads-app-management`: 回呼網址說明需新增 Webhook 回呼網址（`${APP_URL}/threads/webhook`）。

## Impact

- 新增路由：`GET/POST /threads/webhook`（`routes/web.php`）。
- 新增控制器：`app/Http/Controllers/ThreadsWebhookController.php`。
- 新增 Service：`app/Services/ThreadsWebhookService.php`（或類似命名）。
- 新增設定：`config/services.php` 的 `threads.webhook_verify_token`。
- 更新使用說明：`app/Filament/Pages/UsageGuide.php` 與對應 Blade view。
- 依賴：需在 Meta Threads App 設定 Webhook 回呼網址與驗證 token。

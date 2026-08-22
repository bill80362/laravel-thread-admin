## 1. 設定與路由

- [x] 1.1 在 `config/services.php` 的 `threads` 區塊新增 `webhook_verify_token`，讀取 `THREADS_WEBHOOK_VERIFY_TOKEN`
- [x] 1.2 在 `.env.example` 新增 `THREADS_WEBHOOK_VERIFY_TOKEN` 設定範例
- [x] 1.3 在 `routes/web.php` 新增 `GET/POST /threads/webhook` 路由，指向 `ThreadsWebhookController`

## 2. 控制器與 Service

- [x] 2.1 建立 `app/Http/Controllers/ThreadsWebhookController.php`，實作 `GET` 驗證（`hub.mode` / `hub.verify_token` / `hub.challenge`）與 `POST` 事件接收
- [x] 2.2 建立 `app/Services/ThreadsWebhookService.php`，收斂回覆事件處理邏輯（以 `threads_reply_id` 為唯一鍵 `firstOrCreate`，來源標記 `Webhook`，標記未讀）
- [x] 2.3 事件 payload 對應既有貼文/帳號，取得 `threads_account_id` 與 `user_id`；無法對應時記錄警告並略過

## 3. 使用說明

- [x] 3.1 更新 `README.md`，說明 Webhook 回呼網址 `${APP_URL}/threads/webhook` 與 OAuth callback 網址（使用者要求僅更新 README，不更新 UsageGuide）
- [x] 3.2 回覆同步機制說明（README 已涵蓋 Webhook 回呼網址；UsageGuide 依使用者決定本次不更新）

## 4. 測試

- [x] 4.1 撰寫 Webhook 驗證端點測試（驗證成功回傳 challenge、驗證失敗拒絕）
- [x] 4.2 撰寫回覆事件處理測試（建立新回覆、重複事件不重複建立、無法對應帳號時略過）

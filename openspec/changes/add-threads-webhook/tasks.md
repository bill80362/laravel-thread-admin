## 1. 設定與路由

- [ ] 1.1 在 `config/services.php` 的 `threads` 區塊新增 `webhook_verify_token`，讀取 `THREADS_WEBHOOK_VERIFY_TOKEN`
- [ ] 1.2 在 `.env.example` 新增 `THREADS_WEBHOOK_VERIFY_TOKEN` 設定範例
- [ ] 1.3 在 `routes/web.php` 新增 `GET/POST /threads/webhook` 路由，指向 `ThreadsWebhookController`

## 2. 控制器與 Service

- [ ] 2.1 建立 `app/Http/Controllers/ThreadsWebhookController.php`，實作 `GET` 驗證（`hub.mode` / `hub.verify_token` / `hub.challenge`）與 `POST` 事件接收
- [ ] 2.2 建立 `app/Services/ThreadsWebhookService.php`，收斂回覆事件處理邏輯（以 `threads_reply_id` 為唯一鍵 `firstOrCreate`，來源標記 `Webhook`，標記未讀）
- [ ] 2.3 事件 payload 對應既有貼文/帳號，取得 `threads_account_id` 與 `user_id`；無法對應時記錄警告並略過

## 3. 使用說明

- [ ] 3.1 更新 `app/Filament/Pages/UsageGuide.php` 與對應 Blade view，說明 Webhook 回呼網址 `${APP_URL}/threads/webhook` 與驗證 token 設定方式
- [ ] 3.2 更新回覆同步機制說明，從「僅輪詢」改為「輪詢 + Webhook 即時通知」

## 4. 測試

- [ ] 4.1 撰寫 Webhook 驗證端點測試（驗證成功回傳 challenge、驗證失敗拒絕）
- [ ] 4.2 撰寫回覆事件處理測試（建立新回覆、重複事件不重複建立、無法對應帳號時略過）

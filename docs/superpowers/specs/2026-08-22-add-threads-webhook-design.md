# 新增 Threads Webhook 回呼 — 設計文件

日期：2026-08-22
狀態：已批准

## 背景與動機

目前系統僅透過 `CollectThreadsReplies` Job 定期輪詢（每 2 分鐘）抓取 Threads 回覆，回覆非即時。本變更新增 Threads Webhook 接收端點，讓系統在 Threads 上產生新回覆時能即時收到通知並建立回覆記錄。

相關提案：`openspec/changes/add-threads-webhook/`（proposal / specs / design / tasks）。

## 目標 / 非目標

**目標：**
- 提供 Webhook 驗證端點（`GET /threads/webhook`）與事件接收端點（`POST /threads/webhook`）。
- 收到回覆事件時即時建立回覆記錄，來源標記 `webhook`。
- 驗證 token 以環境變數管理。
- 業務邏輯收斂至 `app/Services/` 共用 Service。

**非目標：**
- 不處理 Threads 其他事件類型（如貼文刪除等），僅聚焦回覆事件；架構保留擴充彈性。
- 不實作 Webhook 重試佇列或事件去重持久化（以 `threads_reply_id` 唯一鍵達成冪等）。
- 不處理 Webhook 簽章驗證（Meta Threads 目前未提供 HMAC 簽章，僅靠 verify_token 驗證訂閱）。
- 本次不更新使用說明頁面（UsageGuide），僅實作功能。

## 架構

```
GET/POST /threads/webhook
        │
        ▼
ThreadsWebhookController   ← HTTP 層
   ├─ GET  : 訂閱驗證 (hub.mode / verify_token / challenge)
   └─ POST : 事件接收 → 呼叫 Service
        │
        ▼
ThreadsWebhookService      ← 業務層
   ├─ handleEvent(payload) : 依 event type 分派
   ├─ handleReplyCreated() : 對應帳號/貼文 → firstOrCreate 回覆
   └─ (未來) 其他事件 handler
```

**元件清單：**
- `app/Http/Controllers/ThreadsWebhookController.php` — 處理 HTTP 驗證與事件接收。
- `app/Services/ThreadsWebhookService.php` — 收斂事件處理業務邏輯。
- 路由 `GET/POST /threads/webhook`（`routes/web.php`）。
- 設定 `config/services.php` 的 `threads` 區塊新增 `webhook_verify_token`，讀取 `THREADS_WEBHOOK_VERIFY_TOKEN`。

## 訂閱驗證（GET）

```
GET /threads/webhook?hub.mode=subscribe&hub.verify_token=XXX&hub.challenge=YYY
```

- 驗證 `hub.mode === 'subscribe'` 且 `hub.verify_token === config('services.threads.webhook_verify_token')`。
- 相符 → 回傳 `hub.challenge`（HTTP 200）。
- 不相符 → HTTP 403。

## 事件接收（POST）

- 接收 JSON payload，解析 `entry[].changes[]` 事件。
- 每個事件依 `field` 分派（目前處理 `replies` 欄位）。
- 事件對應帳號/貼文：
  - 用 `media_id` 對應 `posts.threads_media_id` → 取得 `post_id`、`threads_account_id`、`user_id`。
  - 用 `threads_user_id` 對應 `threads_accounts.threads_user_id`（備援）。
- 無法對應 → 記錄警告並略過。
- 建立回覆：`firstOrCreate(['threads_reply_id' => ...])`，來源 `Webhook`，標記未讀。

## 錯誤處理

- 驗證失敗 → 403。
- 事件無法對應 → 記錄警告，回傳 200（避免 Meta 重試）。
- 建立回覆失敗 → 記錄錯誤，回傳 200（冪等保護，重複事件不會重複建立）。

## 測試

- 驗證端點：成功回傳 challenge、失敗回 403。
- 事件處理：建立新回覆、重複事件不重複建立、無法對應時略過。

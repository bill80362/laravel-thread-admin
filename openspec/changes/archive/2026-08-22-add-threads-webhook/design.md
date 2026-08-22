## Context

目前系統僅透過 `CollectThreadsReplies` Job 定期輪詢（每 2 分鐘）抓取回覆，回覆非即時。`ReplySource` enum 已有 `Webhook` 值但未實作接收端點。過去設計文件（`threads-community-management-mvp`）曾因需 App Review（Advanced Access）而暫緩 Webhook，改走 Polling。

現有架構：
- `ThreadsClient` 封裝 Threads Graph API 呼叫。
- `CollectThreadsReplies` 以 `threads_reply_id` 為唯一鍵 `firstOrCreate` 建立回覆，來源標記 `Polling`。
- 回覆歸屬 `user_id` 取自所屬 Threads 帳號的 `user_id`（資料隔離）。
- 設定集中於 `config/services.php` 的 `threads` 區塊，由 `.env` 提供。

## Goals / Non-Goals

**Goals:**
- 提供 Webhook 驗證端點（`GET /threads/webhook`）與事件接收端點（`POST /threads/webhook`）。
- 收到回覆事件時即時建立回覆記錄，來源標記 `webhook`。
- 驗證 token 以環境變數管理。
- 業務邏輯收斂至 `app/Services/` 共用 Service。

**Non-Goals:**
- 不處理 Threads 其他事件類型（如貼文、刪除等），僅聚焦回覆事件。
- 不實作 Webhook 的重試佇列或事件去重持久化（以 `threads_reply_id` 唯一鍵達成冪等）。
- 不處理 Webhook 的簽章驗證（Meta Threads 目前未提供 HMAC 簽章，僅靠 verify_token 驗證訂閱）。

## Decisions

### 1. 端點設計：`GET` 驗證 + `POST` 接收
- **決策**：單一端點 `/threads/webhook`，`GET` 處理訂閱驗證，`POST` 處理事件推送。
- **理由**：符合 Meta Webhook 慣例，單一 URL 即可在 Meta 後台設定。
- **替代方案**：分開兩個端點 — 增加設定複雜度，無實質好處。

### 2. 控制器 + Service 分層
- **決策**：`ThreadsWebhookController` 負責 HTTP 層（驗證、解析 payload、回傳回應），業務邏輯（建立回覆）收斂至 `ThreadsWebhookService`。
- **理由**：符合專案「MCP 工具與後台共用 Service」的既有規範，業務邏輯可被測試與重用。
- **替代方案**：全部寫在控制器 — 違反專案收斂業務邏輯的慣例。

### 3. 驗證 token 以環境變數管理
- **決策**：`config/services.php` 的 `threads` 區塊新增 `webhook_verify_token`，讀取 `THREADS_WEBHOOK_VERIFY_TOKEN`。
- **理由**：與既有 `client_id` / `client_secret` 管理方式一致。
- **替代方案**：存資料庫 — 與先前「移除 ThreadsApp 改為 .env」的方向相悖。

### 4. 回覆建立沿用 `firstOrCreate` 冪等
- **決策**：沿用 `CollectThreadsReplies` 的 `firstOrCreate(['threads_reply_id' => ...])` 模式，來源標記 `Webhook`。
- **理由**：`threads_reply_id` 唯一鍵天然去重，重複事件不會重複建立。
- **替代方案**：自建去重表 — 過度設計，唯一鍵已足夠。

### 5. 事件 payload 對應帳號
- **決策**：事件 payload 中的 `media_id`（或回覆所屬貼文）對應到 `posts.threads_media_id`，進而取得 `threads_account_id` 與 `user_id`。
- **理由**：回覆需歸屬正確的帳號與使用者（資料隔離），與輪詢邏輯一致。
- **注意**：若事件無法對應到既有貼文/帳號，則記錄警告並略過，不建立孤兒回覆。

## Risks / Trade-offs

- [Webhook 需 App Review（Advanced Access）才能啟用] → 設定文件與使用說明需明確標註此前提；未啟用前仍以輪詢為主要同步機制。
- [事件 payload 結構可能因 Meta 調整而變動] → 解析時對欄位做容錯（`??` 預設值），無法對應時記錄警告。
- [無法對應到既有貼文/帳號的回覆被略過] → 記錄警告日誌，供後續排查；不影響既有輪詢資料。
- [Webhook 無簽章驗證，僅靠 verify_token] → 端點僅接受 `POST` 且驗證訂閱 token；事件內容以 `threads_reply_id` 冪等保護，降低偽造影響。

## Migration Plan

1. 新增 `.env` 設定 `THREADS_WEBHOOK_VERIFY_TOKEN`（與 `.env.example`）。
2. 部署後在 Meta Threads App 後台設定 Webhook 回呼網址 `${APP_URL}/threads/webhook` 與驗證 token。
3. 回滾：移除 Webhook 設定即可，輪詢機制不受影響，可隨時退回純輪詢。

## Open Questions

- 是否需要在收到 Webhook 事件時同步觸發「貼文有新回覆」的警示更新？（目前 spec 僅要求建立回覆並標記未讀，警示更新可於實作時與輪詢邏輯對齊。）

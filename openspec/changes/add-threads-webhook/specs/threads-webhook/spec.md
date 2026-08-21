## Purpose

接收 Meta Threads 的 Webhook 驗證與事件推送，讓系統在 Threads 上產生新回覆時能即時收到通知並建立回覆記錄，取代僅靠輪詢的被動同步方式。

## ADDED Requirements

### Requirement: Webhook 驗證端點
系統 SHALL 提供一個 Webhook 端點，用於回應 Meta 的訂閱驗證請求（`hub.mode`、`hub.verify_token`、`hub.challenge`）。

#### Scenario: 驗證成功回傳 challenge
- **WHEN** Meta 以 `hub.mode=subscribe`、`hub.verify_token` 與 `hub.challenge` 呼叫 Webhook 端點
- **AND** `hub.verify_token` 與系統設定的驗證 token 相符
- **THEN** 系統 SHALL 以 HTTP 200 回傳 `hub.challenge` 的內容

#### Scenario: 驗證失敗拒絕請求
- **WHEN** Meta 以 `hub.verify_token` 呼叫 Webhook 端點
- **AND** `hub.verify_token` 與系統設定的驗證 token 不相符
- **THEN** 系統 SHALL 回傳錯誤狀態碼且不回傳 challenge

### Requirement: 接收回覆事件並建立回覆
系統 SHALL 接收 Threads 的回覆事件推送，並將新回覆寫入 `replies` 表，來源標記為 `webhook`。

#### Scenario: 收到新回覆事件建立記錄
- **WHEN** 系統收到 Threads 的回覆事件推送
- **THEN** 系統 SHALL 以 `threads_reply_id` 為唯一鍵建立回覆記錄
- **AND** 該回覆 SHALL 標記來源為 `webhook`
- **AND** 該回覆 SHALL 標記為未讀
- **AND** 該回覆 SHALL 歸屬於事件對應的 Threads 帳號所屬使用者

#### Scenario: 重複事件不重複建立
- **WHEN** 系統收到一筆 `threads_reply_id` 已存在的回覆事件
- **THEN** 系統 SHALL 不重複建立回覆記錄

### Requirement: 驗證 token 以環境變數管理
系統 SHALL 以環境變數（`THREADS_WEBHOOK_VERIFY_TOKEN`）設定 Webhook 驗證 token，並由 `config/services.php` 讀取。

#### Scenario: 從環境變數讀取驗證 token
- **WHEN** 系統處理 Webhook 驗證請求
- **THEN** 系統 SHALL 使用 `config('services.threads.webhook_verify_token')` 作為驗證 token
- **AND** 該值 SHALL 來自 `THREADS_WEBHOOK_VERIFY_TOKEN` 環境變數

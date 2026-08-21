## ADDED Requirements

### Requirement: Webhook 回呼網址
系統 SHALL 提供 Webhook 回呼網址，供 Meta Threads App 設定即時事件通知。

#### Scenario: 提供 Webhook 回呼網址
- **WHEN** 管理者需要設定 Meta Threads App 的 Webhook 回呼網址
- **THEN** 系統 SHALL 提供 `${APP_URL}/threads/webhook` 作為 Webhook 回呼網址
- **AND** 該網址 SHALL 由 `APP_URL` 推導，無需手動設定

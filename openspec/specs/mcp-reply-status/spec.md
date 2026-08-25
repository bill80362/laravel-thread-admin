## Purpose

讓 AI 客戶端（MCP）能變更回覆的處理狀態，方便管理回覆佇列。

## ADDED Requirements

### Requirement: 變更回覆狀態

系統 SHALL 提供 MCP 工具，讓使用者將指定回覆留言的狀態變更為 `new`、`replied`、`ignored` 或 `read`（標記已讀）。

#### Scenario: 將回覆標記為已忽略

- **WHEN** 使用者提供有效的 `reply_id` 與 `status=ignored`
- **THEN** 系統 SHALL 將該回覆的狀態變更為 `ignored`
- **THEN** 系統 SHALL 回傳更新後的回覆資料

#### Scenario: 將回覆標記為已讀

- **WHEN** 使用者提供有效的 `reply_id` 與 `status=read`
- **THEN** 系統 SHALL 將該回覆的 `read_at` 設為目前時間
- **THEN** 系統 SHALL 回傳更新後的回覆資料

#### Scenario: 變更不存在的回覆

- **WHEN** 使用者提供的 `reply_id` 不存在或不屬於該使用者
- **THEN** 系統 SHALL 回傳驗證錯誤

#### Scenario: 將回覆標記為已回覆

- **WHEN** 使用者提供有效的 `reply_id` 與 `status=replied`
- **THEN** 系統 SHALL 將該回覆的狀態變更為 `replied`
- **THEN** 系統 SHALL 將 `replied_at` 設為目前時間
- **THEN** 系統 SHALL 回傳更新後的回覆資料

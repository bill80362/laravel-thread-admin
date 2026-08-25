## Purpose

讓 AI 客戶端（MCP）能對特定回覆留言發出回應，而非僅能對貼文回覆。

## ADDED Requirements

### Requirement: 回應回覆留言

系統 SHALL 提供 MCP 工具，讓使用者對指定的回覆留言（reply）發出回應，回應內容會被排程發佈至 Threads。

#### Scenario: 成功回應回覆留言

- **WHEN** 使用者提供有效的 `reply_id` 與 `text`
- **THEN** 系統 SHALL 建立一筆新回覆記錄並排程發佈
- **THEN** 系統 SHALL 回傳新回覆記錄的 id、text、status

#### Scenario: 回應不存在的回覆

- **WHEN** 使用者提供的 `reply_id` 不存在或不屬於該使用者
- **THEN** 系統 SHALL 回傳驗證錯誤

#### Scenario: 回應缺少 thread_reply_id 的回覆

- **WHEN** 使用者回應的 `reply` 沒有 `threads_reply_id`（即非從 Threads 收集而來）
- **THEN** 系統 SHALL 回退為回應該回覆所屬的貼文

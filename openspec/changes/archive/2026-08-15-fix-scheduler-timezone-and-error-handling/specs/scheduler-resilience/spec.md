## Purpose

確保排程命令中各個子任務的 dispatch 彼此獨立——任一個 Job 的 dispatch 失敗不應阻斷其他排程任務的執行。

## ADDED Requirements

### Requirement: 排程任務獨立容錯
`threads:schedule` 命令在 dispatch 任一 Job（`PublishScheduledPost`、`CollectThreadsReplies`、`RefreshThreadsTokens`）時若發生例外，系統 SHALL 記錄錯誤並繼續執行其餘任務，不得因單一例外導致整個命令返回非零退出碼。

#### Scenario: CollectThreadsReplies dispatch 失敗不影響發文任務
- **WHEN** `dispatchReplyCollection()` 在 dispatch `CollectThreadsReplies` 時拋出例外
- **THEN** 系統 SHALL 記錄該錯誤至 log
- **AND** `dispatchDuePosts()` 已 dispatch 的貼文不受影響
- **AND** `dispatchTokenRefresh()` 繼續執行
- **AND** 命令最終返回 `SUCCESS`（退出碼 0）

#### Scenario: 所有 dispatch 成功
- **WHEN** 所有 dispatch 方法均正常完成
- **THEN** 命令返回 `SUCCESS`（退出碼 0）
- **AND** log 中不出現排程錯誤記錄

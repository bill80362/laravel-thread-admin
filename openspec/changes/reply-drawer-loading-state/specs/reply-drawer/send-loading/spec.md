## Purpose

回覆抽屜在送出回覆時提供即時的 UI 反饋，防止使用者在請求期間重複點擊送出按鈕。

## ADDED Requirements

### Requirement: 送出按鈕顯示 loading 狀態

當使用者按下送出按鈕後、Livewire 請求完成前，送出按鈕 MUST 呈現 loading 狀態，防止再次點擊。

#### Scenario: 請求期間按鈕 disabled
- **WHEN** 使用者按下送出按鈕
- **THEN** 送出按鈕立即設為 disabled，且顯示 loading 指示器

#### Scenario: 請求完成後按鈕恢復
- **WHEN** Livewire 請求完成（成功或失敗）
- **THEN** 送出按鈕恢復為可點擊狀態，回到原始文字

### Requirement: 請求期間 textarea 禁用

當送出請求進行中時，textarea MUST 設為 disabled，防止使用者編輯內容。

#### Scenario: 請求期間 textarea 不可編輯
- **WHEN** 使用者按下送出按鈕
- **THEN** textarea 立即設為 disabled

#### Scenario: 請求完成後 textarea 恢復
- **WHEN** Livewire 請求完成（成功時清空內容，失敗時保留內容）
- **THEN** textarea 恢復為可編輯狀態

### Requirement: 防止雙重提交

在送出請求進行中，MUST 防止使用者再次觸發送出動作。

#### Scenario: 重複點擊不產生第二個請求
- **WHEN** 送出按鈕處於 loading 狀態
- **AND** 使用者再次點擊送出按鈕
- **THEN** 不會觸發第二個 Livewire 請求

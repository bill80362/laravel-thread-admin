# post-deletion Specification

## Purpose

讓營運人員可刪除已發佈到 Threads 的貼文，並追蹤刪除結果。刪除成功才移除本地記錄，失敗則保留記錄並標記為可重試。

## Requirements

### Requirement: 刪除已發佈貼文
系統 SHALL 提供刪除已發佈 Threads 貼文的功能，先呼叫 Threads API 刪除遠端貼文，成功後才移除本地記錄。

#### Scenario: 成功刪除已發佈貼文
- **WHEN** 使用者對狀態為「已發佈」的貼文觸發刪除
- **THEN** 系統 SHALL 將貼文狀態設為「刪除中」
- **AND** 系統 SHALL 呼叫 Threads API `DELETE /{threads-media-id}` 刪除遠端貼文
- **AND** 若 Threads API 回傳成功，系統 SHALL 移除該貼文的本地資料庫記錄

#### Scenario: 刪除 Threads 貼文失敗
- **WHEN** 系統呼叫 Threads API 刪除遠端貼文但 API 回傳錯誤
- **THEN** 系統 SHALL 將貼文狀態設為「刪除失敗」
- **AND** 系統 SHALL 將錯誤訊息寫入 `error_message` 欄位
- **AND** 系統 SHALL 保留該貼文的本地資料庫記錄，不刪除

#### Scenario: 重新觸發刪除失敗的貼文
- **WHEN** 使用者對狀態為「刪除失敗」的貼文再次觸發刪除
- **THEN** 系統 SHALL 重新執行刪除流程（呼叫 Threads API 刪除遠端貼文）
- **AND** 成功則移除本地記錄，失敗則維持「刪除失敗」狀態並更新錯誤訊息

### Requirement: 刪除貼文狀態機
貼文刪除流程 SHALL 遵循明確的狀態轉換規則。

#### Scenario: 刪除狀態轉換
- **WHEN** 貼文狀態為「已發佈」且使用者觸發刪除
- **THEN** 狀態 SHALL 轉換為「刪除中」
- **AND** 刪除成功後記錄被移除
- **AND** 刪除失敗後狀態 SHALL 轉換為「刪除失敗」

#### Scenario: 僅已發佈貼文可刪除
- **WHEN** 使用者嘗試刪除非「已發佈」或非「刪除失敗」狀態的貼文
- **THEN** 系統 SHALL 拒絕操作
- **AND** 草稿、排程中、發佈中、失敗狀態的貼文 SHALL 走本地直接刪除（不呼叫 Threads API）

### Requirement: Token 失效時的刪除處理
系統 SHALL 在刪除過程中若遇到 token 失效，將帳號標記為需重新授權，並將貼文標記為刪除失敗。

#### Scenario: 刪除時 token 失效
- **WHEN** 系統呼叫 Threads API 刪除遠端貼文但 token 已失效
- **THEN** 系統 SHALL 將該 Threads 帳號狀態設為「需重新授權」
- **AND** 系統 SHALL 將貼文狀態設為「刪除失敗」並記錄錯誤原因
- **AND** 使用者重新授權後可再次觸發刪除

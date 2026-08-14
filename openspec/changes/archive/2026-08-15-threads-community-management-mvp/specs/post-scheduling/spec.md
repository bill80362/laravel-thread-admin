## Purpose

讓管理員建立、編輯、刪除純文字貼文（≤500 字元），設定排程時間，由系統在指定時間自動發佈至已綁定的 Threads 帳號，並追蹤發文狀態。

## ADDED Requirements

### Requirement: 建立排程貼文
系統 SHALL 允許管理員為任一已綁定的 Threads 帳號建立排程貼文，內容為純文字（≤500 字元），並指定未來發佈時間。

#### Scenario: 成功建立排程貼文
- **WHEN** 管理員填寫文字內容（≤500 字元）、選擇目標帳號、設定未來時間，並點擊「儲存」
- **THEN** 系統建立貼文記錄，狀態為 `scheduled`，並顯示於排程列表中

#### Scenario: 文字超過 500 字元
- **WHEN** 管理員輸入超過 500 字元的文字內容並嘗試儲存
- **THEN** 系統顯示驗證錯誤「貼文內容不可超過 500 字元」，不建立貼文

#### Scenario: 排程時間為過去
- **WHEN** 管理員設定的發佈時間早於目前時間
- **THEN** 系統顯示驗證錯誤「排程時間必須在未來」，不建立貼文

#### Scenario: 目標帳號為需重新授權狀態
- **WHEN** 管理員選擇的 Threads 帳號狀態為 `needs_reauth`
- **THEN** 系統顯示警告提示「此帳號需要重新授權」，但仍允許建立排程（發佈時再檢查）

### Requirement: 自動發佈排程貼文
系統 SHALL 透過排程（Scheduler）每分鐘檢查是否有到期未發的貼文，若有則透過 Queue Job 依序發佈至 Threads。

#### Scenario: 到期貼文成功發佈
- **WHEN** 排程貼文到達 scheduled_at 時間、帳號 token 有效、API 呼叫成功
- **THEN** 系統依序執行「建立 media container → 等待 30 秒 → 確認狀態 → 發佈」，貼文狀態更新為 `published`，記錄 threads_media_id 與 published_at

#### Scenario: 發佈時 token 已失效
- **WHEN** 貼文到期但目標帳號的 token 已失效（API 回傳 401）
- **THEN** 系統將貼文狀態更新為 `failed`，記錄錯誤訊息為「token 失效」，並將帳號標記為 `needs_reauth`

#### Scenario: 達到 API 發文上限
- **WHEN** 貼文到期但 Threads API 回傳 rate limit 錯誤（24 小時內已達 250 篇上限）
- **THEN** 系統將貼文狀態更新為 `failed`，記錄錯誤訊息為「已達每日發文上限」

### Requirement: 管理排程貼文
系統 SHALL 允許管理員編輯未發佈的貼文內容與排程時間，以及刪除未發佈的貼文。

#### Scenario: 編輯未發佈貼文
- **WHEN** 管理員修改一筆 `scheduled` 或 `draft` 狀態的貼文內容或排程時間並儲存
- **THEN** 系統更新貼文記錄

#### Scenario: 刪除未發佈貼文
- **WHEN** 管理員刪除一筆 `scheduled` 或 `draft` 狀態的貼文
- **THEN** 系統刪除該貼文記錄

#### Scenario: 不可編輯或刪除已發佈貼文
- **WHEN** 管理員嘗試編輯或刪除 `published` 狀態的貼文
- **THEN** 系統拒絕操作，提示「已發佈的貼文不可修改」

### Requirement: 查看發文狀態
系統 SHALL 在排程列表中顯示每篇貼文的狀態（草稿/排程中/發佈中/已發佈/失敗），以及失敗時的錯誤訊息。

#### Scenario: 查看貼文列表
- **WHEN** 管理員進入排程發文頁面
- **THEN** 系統顯示所有貼文列表，包含內容摘要、目標帳號、排程時間、狀態、錯誤訊息（若有）

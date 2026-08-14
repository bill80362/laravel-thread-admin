## Purpose

讓管理員在集中面板查看來自所有綁定帳號的回覆，並直接在平台上快速回覆留言，記錄回覆狀態以追蹤處理進度。

## ADDED Requirements

### Requirement: 查看集中回覆面板
系統 SHALL 以列表形式顯示所有綁定帳號收到的回覆，並支援依帳號、貼文、狀態篩選。

#### Scenario: 查看所有回覆
- **WHEN** 管理員進入回覆面板
- **THEN** 系統顯示所有回覆列表，依時間倒序排列，每筆顯示：作者 username、回覆內容、所屬貼文摘要、回覆時間、狀態、來源帳號

#### Scenario: 依狀態篩選回覆
- **WHEN** 管理員選擇篩選條件為「未回覆」
- **THEN** 系統僅顯示 status = `new` 的回覆

#### Scenario: 依帳號篩選回覆
- **WHEN** 管理員選擇特定 Threads 帳號篩選
- **THEN** 系統僅顯示該帳號的回覆

### Requirement: 快速回覆留言
系統 SHALL 允許管理員在回覆面板直接對任一留言輸入回覆內容並發佈至 Threads。

#### Scenario: 成功回覆留言
- **WHEN** 管理員對一筆回覆輸入文字內容（≤500 字元）並點擊「回覆」
- **THEN** 系統透過 Threads API 發佈回覆，將該回覆狀態更新為 `replied`，記錄 replied_at

#### Scenario: 回覆內容為空
- **WHEN** 管理員未輸入任何內容即點擊「回覆」
- **THEN** 系統顯示驗證錯誤「回覆內容不可為空」

#### Scenario: 回覆失敗（API 錯誤）
- **WHEN** Threads API 回傳錯誤（如 token 失效、rate limit）
- **THEN** 系統顯示錯誤提示，回覆狀態維持不變

### Requirement: 標記回覆為已忽略
系統 SHALL 允許管理員將不需回覆的留言標記為「已忽略」，方便區分已處理與未處理的回覆。

#### Scenario: 忽略留言
- **WHEN** 管理員對一筆 `new` 狀態的回覆點擊「忽略」
- **THEN** 系統將該回覆狀態更新為 `ignored`

### Requirement: 回覆狀態追蹤
系統 SHALL 記錄每筆回覆的處理狀態（新/已回覆/已忽略）與回覆時間。

#### Scenario: 查看回覆歷史
- **WHEN** 管理員查看已回覆的留言
- **THEN** 系統顯示回覆內容與回覆時間

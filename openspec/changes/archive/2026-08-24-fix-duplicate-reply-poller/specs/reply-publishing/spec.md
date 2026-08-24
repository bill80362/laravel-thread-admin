## MODIFIED Requirements

### Requirement: 新增貼文回覆並發佈
系統 SHALL 允許管理者建立一筆「貼文回覆」，並在建立後將該回覆實際發佈到 Threads 上對應的貼文底下。

#### Scenario: 成功新增貼文回覆並發佈
- **WHEN** 管理者指定目標帳號、目標貼文與回覆內容並建立貼文回覆
- **THEN** 系統 SHALL 建立一筆貼文回覆記錄
- **AND** 系統 SHALL 將回覆內容發佈到 Threads 上該貼文底下
- **AND** 發佈成功後回覆狀態 SHALL 標記為已發佈
- **AND** 發佈成功後系統 SHALL 將 Threads API 回傳的 media ID 記錄至該回覆的 `threads_reply_id` 欄位

#### Scenario: 目標貼文不存在或尚未發佈
- **WHEN** 管理者指定的貼文不存在，或該貼文尚未成功發佈至 Threads
- **THEN** 系統 SHALL 回傳錯誤
- **AND** 系統 SHALL 不建立或發佈貼文回覆

#### Scenario: 發佈失敗
- **WHEN** 貼文回覆發佈到 Threads 的過程中失敗
- **THEN** 系統 SHALL 將回覆狀態標記為發佈失敗
- **AND** 系統 SHALL 記錄失敗原因
- **AND** 系統 SHALL 不回寫 `threads_reply_id`

### Requirement: 回應回覆並發佈
系統 SHALL 允許管理者針對回覆列表中的一則留言建立回應，並將回應內容實際發佈到 Threads 上該則留言底下。

#### Scenario: 成功回應回覆
- **WHEN** 管理者針對一則狀態為「未回覆」的留言輸入回應內容並送出
- **THEN** 系統 SHALL 將回應內容發佈到 Threads 上該則留言底下
- **AND** 發佈成功後該留言的狀態 SHALL 標記為已回覆
- **AND** 發佈成功後系統 SHALL 將 Threads API 回傳的 media ID 記錄至該回覆的 `threads_reply_id` 欄位

## ADDED Requirements

### Requirement: 回覆發佈後排程不重複匯入
回覆發佈成功後記錄了 `threads_reply_id`，當排程定期抓取某貼文的所有 Threads 回覆時，系統 SHALL 能正確辨識該回覆已存在，避免重複匯入。

#### Scenario: 排程抓取跳過已發佈的回覆
- **WHEN** 排程執行抓取 Threads 貼文的回覆列表
- **AND** 其中一則回覆的 ID 已存在於資料庫的 `threads_reply_id` 欄位
- **THEN** 系統 SHALL 跳過該則回覆
- **AND** 系統 SHALL 不建立重複的回覆記錄

#### Scenario: 排程僅匯入真正來自 Threads 的新回覆
- **WHEN** 排程執行抓取 Threads 貼文的回覆列表
- **AND** 其中有回覆的 ID 不存在於資料庫
- **THEN** 系統 SHALL 以來源為 `polling` 建立新的回覆記錄

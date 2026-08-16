## Purpose

定義將回覆實際發佈到 Threads 的行為，涵蓋「新增貼文回覆」與「回應回覆」兩種動作，確保後台介面與 MCP 工具遵循一致的發佈規則與狀態追蹤。

## ADDED Requirements

### Requirement: 新增貼文回覆並發佈
系統 SHALL 允許管理者建立一筆「貼文回覆」，並在建立後將該回覆實際發佈到 Threads 上對應的貼文底下。

#### Scenario: 成功新增貼文回覆並發佈
- **WHEN** 管理者指定目標帳號、目標貼文與回覆內容並建立貼文回覆
- **THEN** 系統 SHALL 建立一筆貼文回覆記錄
- **AND** 系統 SHALL 將回覆內容發佈到 Threads 上該貼文底下
- **AND** 發佈成功後回覆狀態 SHALL 標記為已發佈

#### Scenario: 目標貼文不存在或尚未發佈
- **WHEN** 管理者指定的貼文不存在，或該貼文尚未成功發佈至 Threads
- **THEN** 系統 SHALL 回傳錯誤
- **AND** 系統 SHALL 不建立或發佈貼文回覆

#### Scenario: 發佈失敗
- **WHEN** 貼文回覆發佈到 Threads 的過程中失敗
- **THEN** 系統 SHALL 將回覆狀態標記為發佈失敗
- **AND** 系統 SHALL 記錄失敗原因

### Requirement: 回應回覆並發佈
系統 SHALL 允許管理者針對回覆列表中的一則留言建立回應，並將回應內容實際發佈到 Threads 上該則留言底下。

#### Scenario: 成功回應回覆
- **WHEN** 管理者針對一則狀態為「未回覆」的留言輸入回應內容並送出
- **THEN** 系統 SHALL 將回應內容發佈到 Threads 上該則留言底下
- **AND** 發佈成功後該留言的狀態 SHALL 標記為已回覆

#### Scenario: 留言缺少 Threads ID 時無法回應
- **WHEN** 管理者嘗試回應一則沒有 Threads 留言 ID 的記錄
- **THEN** 系統 SHALL 回傳錯誤
- **AND** 系統 SHALL 不發佈回應

### Requirement: 回覆發佈狀態追蹤
系統 SHALL 追蹤每筆回覆的發佈狀態，讓管理者得以分辨「待發佈」「發佈中」「已發佈」「發佈失敗」。

#### Scenario: 狀態流轉
- **WHEN** 回覆被建立並準備發佈
- **THEN** 系統 SHALL 以「待發佈」狀態建立記錄
- **AND** 發佈開始時狀態 SHALL 轉為「發佈中」
- **AND** 發佈成功後狀態 SHALL 轉為「已發佈」
- **AND** 發佈失敗後狀態 SHALL 轉為「發佈失敗」

### Requirement: 後台與 MCP 共用發佈規則
系統 SHALL 讓後台介面與 MCP 工具在建立與發佈回覆時遵循相同的業務規則。

#### Scenario: 一致的發佈行為
- **WHEN** 後台介面或 MCP 工具建立貼文回覆
- **THEN** 兩者 SHALL 建立相同的回覆記錄
- **AND** 兩者 SHALL 觸發相同的發佈流程

### Requirement: 回覆列表顯示發佈延遲說明
系統 SHALL 在回覆列表頁面上方的說明區中，告知管理者回覆發佈採兩階段機制、建立後約延遲一段時間才會顯示在 Threads 上。

#### Scenario: 進入回覆列表頁時顯示發佈延遲說明
- **WHEN** 管理者進入 `/admin/replies` 頁面
- **THEN** 系統 SHALL 在表格上方顯示說明區
- **AND** 說明區 SHALL 包含回覆資料同步間隔說明
- **AND** 說明區 SHALL 包含回覆發佈延遲說明
- **AND** 延遲秒數 SHALL 取自已實作的發佈延遲常數，確保與實作同步

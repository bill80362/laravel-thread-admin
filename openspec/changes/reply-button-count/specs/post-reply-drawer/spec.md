## MODIFIED Requirements

### Requirement: 貼文卡片提供回覆按鈕
系統 SHALL 在排程發文列表的每張貼文卡片上，於「刪除」按鈕旁提供「回覆」按鈕，按鈕上應顯示該貼文的未讀回覆數與回覆總數。

#### Scenario: 點擊回覆按鈕開啟抽屜 (MODIFIED)
- **WHEN** 管理者在貼文卡片點擊「回覆」按鈕
- **THEN** 系統 SHALL 於右側展開回覆抽屜
- **AND** 抽屜 SHALL 顯示該貼文的回覆串

#### Scenario: 按鈕顯示回覆計數 (ADDED)
- **WHEN** 貼文列表渲染貼文卡片的「回覆」按鈕
- **THEN** 按鈕標籤 SHALL 顯示為 `回覆 (<unreadCount>/<totalCount>)` 格式
- **AND** `unreadCount` SHALL 為該貼文 `read_at IS NULL` 的回覆筆數
- **AND** `totalCount` SHALL 為該貼文的回覆總筆數
- **AND** 當 `totalCount` 為 0 時，SHALL 僅顯示 `回覆` 而不顯示 `(0/0)`

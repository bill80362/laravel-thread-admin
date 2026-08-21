# post-reply-drawer Specification

## Purpose
在排程發文列表提供貼文回覆抽屜，讓管理者能直接檢視該貼文在 Threads 上的回覆串、掌握新回覆，並在抽屜內直接回覆貼文，體驗貼近 Threads。

## Requirements

### Requirement: 貼文卡片提供回覆按鈕
系統 SHALL 在排程發文列表的每張貼文卡片上，於「刪除」按鈕旁提供「回覆」按鈕。

#### Scenario: 點擊回覆按鈕開啟抽屜
- **WHEN** 管理者在貼文卡片點擊「回覆」按鈕
- **THEN** 系統 SHALL 於右側展開回覆抽屜
- **AND** 抽屜 SHALL 顯示該貼文的回覆串

### Requirement: 有新回覆警示
系統 SHALL 在貼文有未讀回覆時，於卡片上顯示「有新回覆」警示 badge。

#### Scenario: 貼文有未讀回覆時顯示警示
- **WHEN** 貼文存在至少一筆未讀回覆
- **THEN** 系統 SHALL 在該貼文卡片顯示「有新回覆」警示 badge

#### Scenario: 貼文無未讀回覆時不顯示警示
- **WHEN** 貼文的所有回覆皆已讀
- **THEN** 系統 SHALL 不顯示「有新回覆」警示 badge

### Requirement: 回覆串依時間排序
系統 SHALL 在抽屜內依時間由舊到新（由上而下）顯示該貼文的回覆串，與 Threads 一致。

#### Scenario: 回覆串由舊到新排列
- **WHEN** 管理者開啟抽屜查看回覆串
- **THEN** 回覆 SHALL 由上而下依建立時間由舊到新排列
- **AND** 最新的回覆 SHALL 位於最下方

### Requirement: 開啟抽屜自動捲動到最新回覆
系統 SHALL 在開啟抽屜並載入回覆後，自動捲動回覆串到最底部（最新回覆）。

#### Scenario: 開啟抽屜捲動到最新
- **WHEN** 管理者開啟抽屜且回覆串載入完成
- **THEN** 回覆串 SHALL 自動捲動到最底部
- **AND** 管理者 SHALL 能向上捲動查看較舊的回覆

### Requirement: 抽屜內回覆貼文
系統 SHALL 在抽屜下方提供回覆輸入框，讓管理者回覆該貼文，並與現有回覆發佈機制整合。

#### Scenario: 成功回覆貼文
- **WHEN** 管理者在抽屜輸入回覆內容並送出
- **THEN** 系統 SHALL 建立回覆記錄並觸發發佈至 Threads
- **AND** 新回覆 SHALL 出現在回覆串最下方

#### Scenario: 回覆內容為空
- **WHEN** 管理者送出空白回覆內容
- **THEN** 系統 SHALL 顯示驗證錯誤
- **AND** 系統 SHALL 不建立回覆記錄

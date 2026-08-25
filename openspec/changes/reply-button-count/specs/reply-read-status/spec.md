## MODIFIED Requirements

### Requirement: 計算未讀回覆數
系統 SHALL 能計算每筆貼文的未讀回覆數量，以決定是否顯示「有新回覆」警示，以及貼文卡片「回覆」按鈕上的計數。

#### Scenario: 查詢未讀回覆數 (MODIFIED)
- **WHEN** 系統判斷貼文是否顯示「有新回覆」警示，或計算貼文卡片「回覆」按鈕的計數
- **THEN** 系統 SHALL 依該貼文的未讀回覆數量決定
- **AND** 未讀回覆數大於零時 SHALL 顯示警示
- **AND** 未讀回覆數 SHALL 作為按鈕計數的分子

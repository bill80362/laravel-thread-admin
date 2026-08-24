## MODIFIED Requirements

### Requirement: 計算未讀回覆數
系統 SHALL 能計算每筆貼文的未讀回覆數量，以決定是否顯示「有新回覆」警示。亦可計算全體使用者的全域未讀回覆總數，供側邊欄 badge 使用。

#### Scenario: 查詢未讀回覆數
- **WHEN** 系統判斷貼文是否顯示「有新回覆」警示
- **THEN** 系統 SHALL 依該貼文的未讀回覆數量決定
- **AND** 未讀回覆數大於零時 SHALL 顯示警示

#### Scenario: 查詢全域未讀回覆總數 (ADDED)
- **WHEN** 系統計算側邊欄 badge 顯示的未讀數
- **THEN** 系統 SHALL 計算該使用者全部 `read_at IS NULL` 的回覆筆數
- **AND** 該數值 SHALL 作為 badge 的分子顯示

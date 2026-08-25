# reply-read-status Specification

## Purpose
管理回覆的已讀/未讀狀態，讓管理者能區分已查看與新抓取的回覆，並在開啟回覆抽屜時自動將該貼文的所有回覆標記為已讀。

## Requirements

### Requirement: 回覆具備已讀狀態
系統 SHALL 為每筆回覆記錄已讀狀態，未讀回覆與已讀回覆需可區分。

#### Scenario: 新抓取的回覆為未讀
- **WHEN** 系統透過輪詢抓取到一筆新回覆
- **THEN** 該回覆 SHALL 標記為未讀

#### Scenario: 既有回覆標記為已讀
- **WHEN** 系統升級後處理既有回覆資料
- **THEN** 既有回覆 SHALL 標記為已讀

### Requirement: 開啟抽屜標記已讀
系統 SHALL 在管理者開啟某貼文的回覆抽屜時，將該貼文的所有回覆標記為已讀。

#### Scenario: 開啟抽屜後回覆全部已讀
- **WHEN** 管理者開啟某貼文的回覆抽屜
- **THEN** 該貼文的所有回覆 SHALL 標記為已讀
- **AND** 該貼文的「有新回覆」警示 SHALL 隨之消失

### Requirement: 計算未讀回覆數
系統 SHALL 能計算每筆貼文的未讀回覆數量，以決定是否顯示「有新回覆」警示。亦可計算全體使用者的全域未讀回覆總數，供側邊欄 badge 使用。

#### Scenario: 查詢未讀回覆數
- **WHEN** 系統判斷貼文是否顯示「有新回覆」警示
- **THEN** 系統 SHALL 依該貼文的未讀回覆數量決定
- **AND** 未讀回覆數大於零時 SHALL 顯示警示

#### Scenario: 查詢全域未讀回覆總數
- **WHEN** 系統計算側邊欄 badge 顯示的未讀數
- **THEN** 系統 SHALL 計算該使用者全部 `read_at IS NULL` 的回覆筆數
- **AND** 該數值 SHALL 作為 badge 的分子顯示

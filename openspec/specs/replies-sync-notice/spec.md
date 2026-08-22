# replies-sync-notice Specification

## Purpose
在回覆列表頁面顯示同步機制說明，讓管理者了解回覆資料是透過定期輪詢取得的，並非即時更新，避免管理者誤以為系統故障或資料遺失。

## Requirements

### Requirement: 回覆列表頁顯示同步說明
系統 SHALL 在回覆列表頁面上方顯示資訊提示，說明回覆資料的同步機制，包含定期輪詢與 Webhook 即時通知。

#### Scenario: 進入回覆列表頁時顯示提示
- **WHEN** 管理者進入 `/admin/replies` 頁面
- **THEN** 系統 SHALL 在表格上方顯示資訊提示區塊
- **AND** 提示內容 SHALL 說明回覆資料透過定期輪詢與 Webhook 即時通知取得
- **AND** 提示內容 SHALL 告知新留言可能不會立即顯示

#### Scenario: 提示區塊不影響表格操作
- **WHEN** 提示區塊顯示於回覆列表頁
- **THEN** 表格的搜尋、篩選、排序、回覆操作等功能 SHALL 正常運作

### Requirement: 輪詢抓取的新回覆標記為未讀
系統 SHALL 在透過輪詢建立新回覆時，將該回覆標記為未讀，以便與已讀狀態整合。

#### Scenario: 輪詢建立新回覆為未讀
- **WHEN** 系統透過輪詢抓取到一筆先前不存在的回覆並建立記錄
- **THEN** 該回覆 SHALL 標記為未讀
- **AND** 該回覆所屬貼文 SHALL 顯示「有新回覆」警示

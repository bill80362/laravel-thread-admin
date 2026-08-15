# replies-sync-notice Specification

## Purpose
在回覆列表頁面顯示同步機制說明，讓管理者了解回覆資料是透過定期輪詢取得的，並非即時更新，避免管理者誤以為系統故障或資料遺失。

## Requirements

### Requirement: 回覆列表頁顯示同步說明
系統 SHALL 在回覆列表頁面上方顯示資訊提示，說明回覆資料的同步機制。

#### Scenario: 進入回覆列表頁時顯示提示
- **WHEN** 管理者進入 `/admin/replies` 頁面
- **THEN** 系統 SHALL 在表格上方顯示資訊提示區塊
- **AND** 提示內容 SHALL 說明回覆資料每 5 分鐘自動同步一次
- **AND** 提示內容 SHALL 告知新留言可能不會立即顯示

#### Scenario: 提示區塊不影響表格操作
- **WHEN** 提示區塊顯示於回覆列表頁
- **THEN** 表格的搜尋、篩選、排序、回覆操作等功能 SHALL 正常運作

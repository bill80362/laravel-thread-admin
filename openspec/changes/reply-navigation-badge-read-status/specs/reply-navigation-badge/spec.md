## Purpose

在後台側邊導覽列「回覆面板」選單旁顯示未讀回覆數量與總數的 badge，讓管理者不需進入頁面即可掌握新回覆狀況；點擊進入回覆面板頁面時自動將所有未讀回覆標記為已讀。

## ADDED Requirements

### Requirement: 側邊欄顯示未讀/總數 badge
系統 SHALL 在側邊導覽列的「回覆面板」選單項目旁顯示 `${未讀數}/${總數}` 格式的 badge。

#### Scenario: 有未讀回覆時顯示 badge
- **WHEN** 管理者進入後台，且 replies 表中存在 `read_at IS NULL` 的回覆
- **THEN** 側邊欄「回覆面板」旁 SHALL 顯示 `${未讀數}/${總數}` 的 badge

#### Scenario: 無未讀回覆時 badge 為 `0/總數`
- **WHEN** 管理者進入後台，且 replies 表中所有回覆皆已讀（`read_at IS NOT NULL`）
- **THEN** 側邊欄「回覆面板」旁 SHALL 顯示 `0/${總數}` 的 badge

### Requirement: 進入回覆面板頁面自動標記已讀
系統 SHALL 在管理者進入回覆面板頁面時，自動將該使用者所有未讀回覆標記為已讀。

#### Scenario: 進入回覆面板頁面後未讀歸零
- **WHEN** 管理者進入回覆面板頁面（`/user/replies`）
- **THEN** 該管理者所有 `read_at IS NULL` 的回覆 SHALL 更新為 `read_at = now()`
- **AND** 該管理者的未讀回覆數 SHALL 歸零

#### Scenario: 側邊欄 badge 同步更新
- **WHEN** 管理者進入回覆面板頁面且未讀回覆已標記為已讀
- **THEN** 側邊欄「回覆面板」badge SHALL 更新為 `0/${總數}`

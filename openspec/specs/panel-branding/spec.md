# panel-branding Specification

## Purpose

讓 admin 後台與 user 前台這兩個 Filament 面板擁有各自獨立的品牌標題（logo、分頁標題、登入頁名牌），並與內部識別名 `APP_NAME` 解耦，避免中文命名造成系統內部問題。

## Requirements

### Requirement: 各面板可有獨立品牌名稱
系統 SHALL 讓 admin 與 user 兩個面板各自擁有獨立的品牌名稱，供面板的 logo、瀏覽器分頁標題與登入頁顯示，且互不影響。

#### Scenario: Admin 面板顯示其品牌名稱
- **WHEN** 使用者瀏覽 admin 面板（含登入頁與已登入頁面）
- **THEN** 面板的 logo、分頁標題與登入頁 SHALL 顯示 admin 面板設定的品牌名稱

#### Scenario: User 面板顯示其品牌名稱
- **WHEN** 使用者瀏覽 user 面板（含登入頁與已登入頁面）
- **THEN** 面板的 logo、分頁標題與登入頁 SHALL 顯示 user 面板設定的品牌名稱

### Requirement: 品牌名稱與內部識別名解耦
品牌名稱 SHALL 獨立於 `APP_NAME` 設定，`APP_NAME` 僅作為英文內部識別名（如 cache/session prefix）使用，其值不影響面板顯示的品牌名稱。

#### Scenario: APP_NAME 為英文內部識別名
- **WHEN** 設定品牌名稱（可能為中文）且 `APP_NAME` 為英文
- **THEN** 面板顯示 SHALL 使用品牌名稱，而內部 cache/session prefix SHALL 仍由 `APP_NAME` 決定

#### Scenario: 品牌名稱支援非 ASCII 字元
- **WHEN** 品牌名稱設定為中文等非 ASCII 字元
- **THEN** 面板 logo、分頁標題與登入頁 SHALL 正常顯示該名稱，且不影響任何內部識別設定

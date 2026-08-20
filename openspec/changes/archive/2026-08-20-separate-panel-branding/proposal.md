## Why

目前 Filament 兩個 panel（admin 後台、user 前台）的網站標題（`fi-logo`、瀏覽器分頁 `<title>`、登入頁品牌）全都由 `config('app.name')` 回退到 `APP_NAME` 決定。這導致兩個問題：(1) admin 與 user 無法各自顯示不同的品牌名稱；(2) `APP_NAME` 同時肩負「內部識別名」（作 cache / session / Redis prefix 用，會被 `Str::slug()`）與「對外顯示品牌名」兩種角色，一旦設定為中文，內部 prefix 會被 slug 成空白而產生 BUG。

## What Changes

- 新增 `ADMIN_BRAND` 與 `USER_BRAND` 兩個環境變數，作為 admin / user 兩個 panel 各自的對外品牌名稱（支援中文）。
- 在 `config/filament.php` 新增集中式 `branding` 設定節點，收斂兩者的品牌名稱設定。
- 在 `AdminPanelProvider` 呼叫 `->brandName()` 帶入 `ADMIN_BRAND`；在 `UserPanelProvider` 呼叫 `->brandName()` 帶入 `USER_BRAND`。
- 更新 `.env` 與 `.env.example`，新增兩個環境變數說明。
- `APP_NAME` 從此僅作為英文內部識別名，不再直接決定 Filament 顯示的標題。

## Capabilities

### New Capabilities
- `panel-branding`: 各 Filament 面板的品牌標題設定能力，讓 admin 與 user 面板擁有各自獨立的品牌名稱，並與內部識別名 `APP_NAME` 解耦。

### Modified Capabilities
<!-- 無既有 spec 之行為變更 -->

## Impact

- 受影響檔案：
  - `app/Providers/Filament/AdminPanelProvider.php`
  - `app/Providers/Filament/UserPanelProvider.php`
  - `config/filament.php`（新增 `branding` 節點）
  - `.env`、`.env.example`
- 相依套件：無新增；使用 Filament 內建 `->brandName()` API。
- 行為影響：admin / user 面板的 logo、分頁標題、登入頁品牌名稱將各自獨立；不影響內部 cache／session prefix（仍由英文 `APP_NAME` 決定）。

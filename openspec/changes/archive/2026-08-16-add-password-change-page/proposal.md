## Why

目前後台使用者登入後，沒有任何地方可以修改自己的密碼。Filament 內建的 `EditProfile` 頁面未被啟用，導致右上角使用者選單缺少「個人資料」入口。使用者需要一個只能修改密碼的介面，同時希望整個後台預設顯示繁體中文。

## What Changes

- 新增一個客製化頁面 `EditPassword`，繼承 Filament 內建的 `Filament\Auth\Pages\EditProfile`，只保留密碼相關欄位（目前密碼、新密碼、確認新密碼）。
- 在 `AdminPanelProvider` 以 `->profile(EditPassword::class)` 註冊該頁面，讓右上角使用者選單出現「個人資料」入口（進入後僅能修改密碼）。
- 移除姓名（name）與電子郵件（email）欄位，使 Email 無法被修改。
- 將應用程式預設語系從 `en` 改為 `zh_TW`，讓 Filament 後台與密碼頁標籤自動顯示繁體中文。

## Capabilities

### New Capabilities
- `password-change`: 後台使用者可以透過右上角使用者選單進入個人資料頁面，並修改自己的密碼（需驗證目前密碼、新密碼確認）。

### Modified Capabilities
<!-- 無既有 capability 的需求變更 -->

## Impact

- 新增檔案：`app/Filament/Pages/EditPassword.php`。
- 修改檔案：`app/Providers/Filament/AdminPanelProvider.php`（註冊 profile 頁面）。
- 修改檔案：`config/app.php`（`locale` 預設值 `en` → `zh_TW`）。
- 依賴既有 Filament `zh_TW` 語系檔（`vendor/filament/filament/resources/lang/zh_TW/...`），無需新增語系檔。
- 不變更資料庫結構；`User` model 的 `password` 欄位已具備 `hashed` cast，無需調整。

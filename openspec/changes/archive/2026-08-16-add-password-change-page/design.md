## Context

- 專案使用 Filament v5.7.6，其內建 `Filament\Auth\Pages\EditProfile` 頁面已實作完整的密碼修改邏輯（目前密碼驗證、新密碼 `Password::default()` 規則、`Hash::make` 寫入、確認密碼比對、成功通知、session 密碼 hash 同步）。
- `AdminPanelProvider` 目前僅呼叫 `->login()`，未啟用 profile 頁面，因此右上角使用者選單沒有「個人資料」入口。
- `User` model 的 `password` 欄位已具備 `hashed` cast（`app/Models/User.php`）。
- Filament 內建 `zh_TW` 語系檔已存在（`vendor/filament/filament/resources/lang/zh_TW/auth/pages/edit-profile.php`），無需新增。
- `config/app.php` 的 `locale` 目前預設為 `en`（讀 `APP_LOCALE`）。

## Goals / Non-Goals

**Goals:**
- 啟用 Filament profile 頁面，且只保留「目前密碼」「新密碼」「確認新密碼」三個欄位。
- 隱藏姓名與電子郵件欄位，使 Email 無法透過該頁修改。
- 讓預設語系為 `zh_TW`。

**Non-Goals:**
- 不改動姓名與電子郵件的顯示或編輯邏輯（完全移除，不做唯讀展示）。
- 不新增兩步驟驗證（2FA）設定介面。
- 不更動資料庫結構或 `User` model 的欄位定義。

## Decisions

### 決策 1：繼承 `EditProfile` 並覆寫 `form()`，而非自訂全新 Page

- **選擇**：建立 `App\Filament\Pages\EditPassword`，`extends \Filament\Auth\Pages\EditProfile`，覆寫 `form(Schema $schema)` 只回傳三個密碼欄位。
- **理由**：複用內建 `save()`、目前密碼驗證、Hash 寫入、session 同步、速率限制等既有邏輯，避免重複實作與引入安全漏洞。
- **替代方案**：自訂全新 `Page` 並手寫密碼更新邏輯 —— 成本高、易出錯，捨棄。

### 決策 2：透過 `->profile()` 註冊自訂頁面

- **選擇**：在 `AdminPanelProvider` 加入 `->profile(\App\Filament\Pages\EditPassword::class)`。
- **理由**：`profile()` 同時註冊頁面、路由與右上角選單項目，並控制 `simple` 版面。
- **替代方案**：手動 `discoverPages` + 自訂選單 —— 較繁瑣，捨棄。

### 決策 3：預設語系改 `zh_TW`

- **選擇**：將 `config/app.php` 的 `locale` 預設值改為 `env('APP_LOCALE', 'zh_TW')`。
- **理由**：保留以 `APP_LOCALE` 環境變數覆寫的彈性，同時讓未設定時預設顯示繁體中文。
- **替代方案**：僅在 Filament 面板內覆寫語系 —— 影響範圍不完整，捨棄。

### 決策 4：密碼欄位沿用內建元件

- **選擇**：直接呼叫父類別提供的 `getPasswordFormComponent()`、`getPasswordConfirmationFormComponent()`、`getCurrentPasswordFormComponent()`。
- **理由**：這些方法已封裝正確的驗證與 `dehydrated` 行為，改寫風險低。

## Risks / Trade-offs

- [隱藏姓名/Email 欄位後，`save()` 的 `handleRecordUpdate` 仍會以 `$data` 更新 user，但因為表單只剩密碼欄位，`$data` 不會包含 name/email，因此不會誤改] → 無需額外防護；若未來要擴充欄位需再評估。
- [`profile()` 預設 `isSimple = true` 使用簡潔版面] → 對單一密碼表單而言是合理的視覺呈現，若需完整版面可再調整。
- [語系預設改 `zh_TW` 會影響整個後台所有 Filament 介面語言] → 這是使用者明確要求的預設行為；仍可透過 `APP_LOCALE` 覆寫回其他語系。

## 1. 建立修改密碼頁面

- [x] 1.1 新增 `app/Filament/Pages/EditPassword.php`，繼承 `Filament\Auth\Pages\EditProfile`，覆寫 `form(Schema $schema)` 只回傳「目前密碼」「新密碼」「確認新密碼」三個欄位（呼叫父類別的 `getCurrentPasswordFormComponent()`、`getPasswordFormComponent()`、`getPasswordConfirmationFormComponent()`）

## 2. 註冊 profile 頁面

- [x] 2.1 在 `app/Providers/Filament/AdminPanelProvider.php` 的 `panel()` 中加入 `->profile(\App\Filament\Pages\EditPassword::class)`

## 3. 設定預設語系

- [x] 3.1 將 `config/app.php` 的 `locale` 預設值改為 `env('APP_LOCALE', 'zh_TW')`

## 4. 驗證

- [x] 4.1 執行 `php artisan route:list` 確認 profile 路由已註冊
- [x] 4.2 執行 `vendor/bin/pint --dirty --format agent` 修正程式碼格式

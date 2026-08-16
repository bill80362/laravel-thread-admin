## 1. 使用說明頁面

- [x] 1.1 執行 `php artisan make:filament-page UsageGuide --no-interaction` 建立 Page class 與 Blade view
- [x] 1.2 在 `app/Filament/Pages/UsageGuide.php` 設定 `$navigationIcon`、`$navigationLabel`（使用說明）、`$navigationSort`（50）、`$title`
- [x] 1.3 於 `resources/views/filament/pages/usage-guide.blade.php` 撰寫「前置準備：申請 Meta App」章節（網站網址、建立應用程式、取得應用程式編號與密鑰、加入 Threads 測試人員）
- [x] 1.4 撰寫「本系統設定 Threads App 與綁定帳號」章節（非程式語言、步驟化）
- [x] 1.5 撰寫「排程發文」章節（狀態機流程、每分鐘觸發、30 秒兩階段、失敗重試）
- [x] 1.6 撰寫「回覆收集」章節（僅偵測本系統發出的貼文、每 5 分鐘）
- [x] 1.7 撰寫「MCP 服務設定」章節（本地模式、HTTP 模式、ChatGPT 註冊步驟、Claude Desktop 註冊步驟）
- [x] 1.8 執行 `vendor/bin/pint --dirty --format agent` 驗證格式

## 2. MCP 控管 Resource

- [x] 2.1 執行 `php artisan make:filament-resource McpToken --no-interaction` 建立 Resource（model 指定 `Laravel\Passport\Token`）
- [x] 2.2 在 `McpTokenResource` 設定 `$model = \Laravel\Passport\Token::class`、`$navigationIcon`、`$navigationLabel`（MCP 控管）、`$navigationSort`（60）、`$modelLabel`
- [x] 2.3 覆寫 `getEloquentQuery()` 限定 `where('user_id', auth()->id())`
- [x] 2.4 設定列表欄位：Client 名稱（`client.name`）、授權範圍（`scopes` badge）、建立時間（`created_at`）、到期時間（`expires_at`）、撤銷狀態（`revoked`）
- [x] 2.5 移除不必要的 create/edit 頁面，僅保留 index（唯讀列表）
- [x] 2.6 新增「註銷」Action：呼叫 `$record->revoke()` 並送出通知
- [x] 2.7 執行 `vendor/bin/pint --dirty --format agent` 驗證格式

## 3. 測試

- [x] 3.1 建立 `tests/Feature/McpTokenResourceTest.php`：驗證登入使用者只能看到自己的 token、revoke 動作、空清單
- [x] 3.2 執行 `php artisan test --compact tests/Feature/McpTokenResourceTest.php` 確認通過

## 4. 文件與收斂

- [x] 4.1 更新 `AGENTS.md`：加入「使用說明」與「MCP 控管」開發規範（含 token 不洩漏 secret、說明內容需與排程常數同步）
- [x] 4.2 執行 `vendor/bin/pint --dirty --format agent` 修正格式
- [x] 4.3 執行 `php artisan test --compact` 確認全數通過
- [x] 4.4 回報完成：提供建議 commit 訊息與變更檔案清單

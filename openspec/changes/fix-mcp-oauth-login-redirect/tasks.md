## 1. 實作修正

- [x] 1.1 在 `bootstrap/app.php` 的 `withMiddleware` 中，加入 `redirectGuestsTo(fn () => Filament::getLoginUrl())`，覆寫 Laravel 預設的 `route('login')` 重導向
- [x] 1.2 加入 `Filament\Facades\Filament` 的 `use` 匯入
- [x] 1.3 在 `bootstrap/app.php` 的 `withExceptions` 中，將 `mcp/*` 納入 `shouldRenderJsonWhen` 判斷

## 2. 驗證

- [x] 2.1 確認未登入存取 `/oauth/authorize` 時，重導向至 `/admin/login` 而非拋出 500 錯誤
- [x] 2.2 執行相關測試（若有）並執行 Pint 格式化
- [x] 2.3 確認 MCP 端點（`/mcp/*`）認證失敗時回傳 JSON 401 而非 HTML 重導向

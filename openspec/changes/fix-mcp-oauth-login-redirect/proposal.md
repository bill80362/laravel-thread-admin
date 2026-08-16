## Why

MCP HTTP 模式的 OAuth 授權流程在「使用者尚未登入」時會拋出 `Route [login] not defined` 錯誤（HTTP 500），導致 AI agent 無法完成 OAuth 認證。原因是 Laravel 預設的未認證重導向目標為名為 `login` 的路由，但本專案使用 Filament 後台，登入路由名稱為 `filament.admin.auth.login`，並不存在 `login` 路由。

## What Changes

- 將全域的未認證重導向目標，從 Laravel 預設的 `route('login')` 改為指向 Filament 後台登入頁（`Filament::getLoginUrl()`）。
- 確保當使用者未登入而存取 `/oauth/authorize` 時，系統會將使用者重導向到 Filament 登入頁，登入後再回到授權流程。
- 讓 MCP 端點（`/mcp/*`）在 `auth:api` 認證失敗時回傳 JSON 401，而非 HTML 重導向，確保 AI agent 能正確處理認證錯誤。

## Capabilities

### New Capabilities

（無）

### Modified Capabilities

- `mcp-server`: 補強 HTTP 模式 OAuth 授權流程的未登入處理行為——未登入時應重導向至後台登入頁，而非拋出 `Route [login] not defined` 錯誤；且 MCP 端點認證失敗時應回傳 JSON 401 而非 HTML 重導向。

## Impact

- 修改 `bootstrap/app.php` 的 `withMiddleware` 設定，加入 `redirectGuestsTo` 指向 Filament 登入 URL。
- 修改 `bootstrap/app.php` 的 `withExceptions` 設定，將 `mcp/*` 納入 `shouldRenderJsonWhen` 判斷。
- 影響範圍：所有使用 Laravel 預設未認證重導向的流程（含 Passport OAuth 授權流程），以及 MCP 端點的錯誤回應格式。
- 不影響已登入使用者的既有行為，亦不影響 Filament 自身的登入中介層。

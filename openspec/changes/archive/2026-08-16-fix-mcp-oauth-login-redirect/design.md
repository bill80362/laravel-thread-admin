## Context

本專案使用 Filament 後台，登入路由名稱為 `filament.admin.auth.login`（URL 為 `/admin/login`）。專案不存在名為 `login` 的路由。

Laravel 的 `ApplicationBuilder::withMiddleware()` 預設會註冊全域的未認證重導向回呼：

```php
->redirectGuestsTo(fn () => route('login'));
```

當 Passport 的 `AuthorizationController` 在未登入狀態下呼叫 `promptForLogin()`，會拋出 `AuthenticationException`。該例外最終由 `Handler::unauthenticated()` 處理，呼叫 `AuthenticationException::redirectTo()`，進而執行上述預設回呼 `route('login')`，導致 `Route [login] not defined`。

## Goals / Non-Goals

**Goals:**
- 讓全域未認證重導向指向 Filament 登入頁，修復 OAuth 授權流程。
- 登入後能返回原授權流程（保留 `guest()` 的 intended URL 機制）。

**Non-Goals:**
- 不引入自訂 Exception Handler 或自訂 middleware。
- 不變更 Passport 或 laravel/mcp 套件原始碼。
- 不改變已登入使用者的行為。

## Decisions

### 決策：在 `bootstrap/app.php` 的 `withMiddleware` 中覆寫 `redirectGuestsTo`

使用 `Filament\Facades\Filament::getLoginUrl()` 作為重導向目標：

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware
        ->redirectGuestsTo(fn () => Filament::getLoginUrl())
        ->trustProxies(...);
})
```

**理由：**
- `redirectGuestsTo()` 接受 `callable`，延遲到實際重導向時才解析 URL，避免 boot 初期 Filament 尚未完全初始化。
- 全域覆寫可讓所有依賴 Laravel 預設未認證重導向的流程（含 Passport OAuth）一致地指向後台登入頁。

**替代方案考量：**
- **僅針對 `/oauth/authorize` 加 middleware**：需額外維護客製 middleware，且 Passport 的 `AuthenticationException` 仍會走全域 handler，無法只靠 route middleware 解決。
- **定義名為 `login` 的別名路由**：可暫時繞過錯誤，但會與 Filament 的登入概念重複，且語意不清晰，非正道。
- **使用 `Filament::getLoginUrl()` 回傳值可能為 `null`**：在本專案後台面板必定存在登入頁，實務上不會為 null；若為 null 則回傳 `null` 讓 handler 回傳 401，不會再拋例外。

## Risks / Trade-offs

- [全域覆寫影響所有未認證重導向] → 目前專案僅 Filament 後台與 Passport OAuth 會用到未認證重導向，兩者都應指向後台登入頁，風險低。
- [`Filament::getLoginUrl()` 依賴 Filament 面板設定] → 已驗證本專案回傳 `https://.../admin/login`，且 Filament 為核心依賴，不會移除。

## Decisions (補充)

### 決策：將 `mcp/*` 納入 `shouldRenderJsonWhen` 判斷

MCP 端點的路徑為 `/mcp/threads`，不在原本 `shouldRenderJsonWhen` 的 `api/*` 範圍內，導致 `auth:api` 認證失敗時走 HTML 重導向。修正為：

```php
$exceptions->shouldRenderJsonWhen(
    fn (Request $request) => $request->is('api/*', 'mcp/*') || $request->expectsJson(),
);
```

**理由：**
- MCP 協定要求錯誤以 JSON-RPC 格式回傳，HTML 重導向會讓 AI agent 無法解析。
- `$request->is()` 支援多個 glob 模式，直接擴充即可，無需自訂 handler。

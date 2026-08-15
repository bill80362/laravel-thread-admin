## Why

應用程式部署在反向代理之後，TLS 由代理終止，Laravel 收到的請求 scheme 為 `http`。導致 `route()` / `url()` / `redirect()` / `asset()` / `@vite()` 產生的對外網址（含 js/css）全部走 `http`，瀏覽器觸發 mixed content 警告並可能封鎖資源。

## What Changes

- 在 `bootstrap/app.php` 啟用 `trustProxies(at: '*')`，信任所有反向代理轉送的 `X-Forwarded-*` header（For / Host / Port / Proto），還原真實 scheme 與 host。
- 在 `config/app.php` 新增 `force_https` 設定，由 `FORCE_HTTPS` 環境變數控制，**預設關閉（false）**。
- 在 `AppServiceProvider::boot()` 中，當 `force_https` 為 true 時呼叫 `URL::forceScheme('https')`，讓所有對外網址（含 js/css）強制走 https。
- 在 `.env.example` 新增 `FORCE_HTTPS=false` 與 `SESSION_SECURE_COOKIE=true` 兩個環境變數範本。

## Capabilities

### New Capabilities

- `https-enforcement`: 應用程式在反向代理後強制所有對外網址（含 js/css）走 https，可透過環境變數開關，預設關閉。

### Modified Capabilities

<!-- 無既有規格需修改 -->

## Impact

- **修改檔案**：
  - `bootstrap/app.php`（啟用 `trustProxies`）
  - `config/app.php`（新增 `force_https` 設定）
  - `app/Providers/AppServiceProvider.php`（依設定呼叫 `URL::forceScheme('https')`）
  - `.env.example`（新增 `FORCE_HTTPS`、`SESSION_SECURE_COOKIE` 範本）

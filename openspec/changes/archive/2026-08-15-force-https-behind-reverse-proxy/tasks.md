## 1. 信任反向代理

- [x] 1.1 在 `bootstrap/app.php` 的 `withMiddleware` 中加入 `trustProxies(at: '*')`，信任 `X-Forwarded-For` / `Host` / `Port` / `Proto` header

## 2. 強制 https 開關

- [x] 2.1 在 `config/app.php` 新增 `'force_https' => env('FORCE_HTTPS', false)` 設定項
- [x] 2.2 在 `AppServiceProvider::boot()` 中，當 `config('app.force_https')` 為 true 時呼叫 `URL::forceScheme('https')`

## 3. 環境變數範本

- [x] 3.1 在 `.env.example` 新增 `FORCE_HTTPS=false`
- [x] 3.2 在 `.env.example` 新增 `SESSION_SECURE_COOKIE=true`

## 4. 程式碼格式化

- [x] 4.1 執行 `vendor/bin/pint --format agent` 確認格式正確

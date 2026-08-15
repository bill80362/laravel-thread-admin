## 設計

### 問題

`ThreadsClient` 使用單一 `API_BASE = 'https://graph.threads.net/v1.0'` 作為所有請求的 base URL。但 Threads API 的 OAuth token 端點（`/oauth/access_token`、`/access_token`、`/refresh_access_token`）**不屬於 v1.0 API**，不應有 `/v1.0` 前綴。

根據 [Threads API 官方文件](https://developers.facebook.com/docs/threads)：

- `POST /oauth/access_token` — 交換 authorization code 為 short-lived token（base: `https://graph.threads.net`）
- `GET /access_token` — 交換 short-lived token 為 long-lived token（base: `https://graph.threads.net`）
- `GET /refresh_access_token` — 刷新 long-lived token（base: `https://graph.threads.net`）

而其他 API（如 `/me`、`/{user_id}/threads`、`/{media_id}/replies` 等）才需要 `/v1.0` 前綴。

### 修復方案

新增 `OAUTH_BASE` 常數，並讓三個 OAuth token 方法使用它：

```php
private const API_BASE = 'https://graph.threads.net/v1.0';
private const OAUTH_BASE = 'https://graph.threads.net';
```

同時在 `ThreadsOAuthController::callback()` 的 catch 區塊加入 `Log::error()` 以便排查問題。

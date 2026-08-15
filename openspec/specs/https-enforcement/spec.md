# https-enforcement Specification

## Purpose
確保部署在反向代理後的應用程式，所有對外產生的網址（含靜態資源 js/css）均使用 https scheme，並可透過環境變數開關此行為。

## Requirements

### Requirement: 信任反向代理 header
系統 SHALL 信任所有反向代理轉送的 `X-Forwarded-For`、`X-Forwarded-Host`、`X-Forwarded-Port`、`X-Forwarded-Proto` header，以還原真實的請求 scheme 與 host。

#### Scenario: 反向代理轉送 https 請求
- **WHEN** 反向代理以 `X-Forwarded-Proto: https` 轉送請求至應用程式
- **THEN** 應用程式產生的所有 URL（`route()`、`url()`、`asset()`、`@vite()`）SHALL 使用 `https` scheme

### Requirement: 強制 https 開關
系統 SHALL 提供 `FORCE_HTTPS` 環境變數，預設值為 `false`。當設為 `true` 時，所有對外產生的網址 SHALL 強制使用 `https` scheme，無論請求實際 scheme 為何。

#### Scenario: 預設關閉
- **WHEN** `FORCE_HTTPS` 未設定或設為 `false`
- **THEN** 應用程式 SHALL 依請求實際 scheme 產生網址，不強制覆寫

#### Scenario: 啟用強制 https
- **WHEN** `FORCE_HTTPS` 設為 `true`
- **THEN** `route()`、`url()`、`redirect()`、`asset()`、`@vite()` 產生的所有網址 SHALL 使用 `https` scheme

### Requirement: Session Secure Cookie 環境變數
系統 SHALL 在 `.env.example` 中提供 `SESSION_SECURE_COOKIE=true` 範本，引導部署者在 https 環境中啟用 session cookie 的 `Secure` 屬性。

#### Scenario: 環境變數範本存在
- **WHEN** 開發者查看 `.env.example`
- **THEN** SHALL 看到 `SESSION_SECURE_COOKIE=true` 與 `FORCE_HTTPS=false` 兩個變數及其預設值

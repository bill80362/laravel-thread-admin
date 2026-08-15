## Context

應用程式部署在反向代理（Nginx / Caddy / Cloudflare 等）之後，TLS 由代理終止。Laravel 13.x 內建 `TrustProxies` middleware 與 `URL::forceScheme()` 方法，無需額外依賴。見 proposal.md - Why。

## Goals / Non-Goals

**Goals:**
- 信任所有反向代理轉送的 `X-Forwarded-*` header
- 提供 `FORCE_HTTPS` 環境變數控制是否強制 https，預設關閉
- 在 `.env.example` 補上 `FORCE_HTTPS` 與 `SESSION_SECURE_COOKIE` 範本

**Non-Goals:**
- 不在應用層做 http→https 的 301 重定向（應由反向代理處理）
- 不設定 `TrustHosts`（目前無此需求）
- 不修改 Filament 或 Livewire 的內部 asset 邏輯（`forceScheme` 已涵蓋）

## Decisions

### 1. 使用 `trustProxies(at: '*')` 而非指定 IP

- **理由**：使用者明確表示「不用管哪一種，都信任」。在 Cloudflare / Docker / 多層代理等場景下，代理 IP 可能動態變化，`*` 最簡單且無維護成本。
- **替代方案**：指定具體 IP 或 CIDR → 更安全但需要維護，且使用者已表明不需要。

### 2. 使用 `URL::forceScheme('https')` 而非僅依賴 `trustProxies`

- **理由**：`trustProxies` 還原的是 proxy 實際送來的值，若 proxy 未正確設定 `X-Forwarded-Proto`，URL 仍會是 http。`forceScheme` 作為保險，確保所有對外網址一律 https。
- **替代方案**：僅設 `APP_URL=https://...` → 只影響以 base URL 為基準的生成，不保證所有動態 URL（如 `@vite()` 的 hot reload、`asset()` 等）都走 https。

### 3. 環境變數 `FORCE_HTTPS` 預設 `false`

- **理由**：本地開發通常走 http，預設關閉避免影響開發體驗。部署時由 ops 在 `.env` 中設為 `true`。
- **替代方案**：預設 `true` → 會影響本地開發，需開發者手動關閉，體驗較差。

### 4. 在 `config/app.php` 新增 `force_https` 設定而非直接讀 `env()`

- **理由**：遵循 Laravel 慣例，config 集中管理，`env()` 只在 config 檔案中呼叫。`AppServiceProvider` 讀 `config('app.force_https')`。
- **替代方案**：在 `AppServiceProvider` 直接 `env('FORCE_HTTPS')` → 違反 Laravel 慣例，且無法被 config cache 覆蓋。

## Risks / Trade-offs

- **`trustProxies(at: '*')` 的安全性**：信任所有代理意味著若攻擊者能直接訪問應用（繞過代理），可偽造 `X-Forwarded-*` header。→ 緩解：生產環境應確保應用只接受來自反向代理的請求（防火牆 / 安全群組）。
- **`forceScheme` 影響 CLI / Queue**：`URL::forceScheme('https')` 在 CLI 環境（artisan、queue worker）中也會生效，可能影響通知郵件中的連結等。→ 這是預期行為，因為 queue worker 處理的任務也應產生 https 連結。

## Migration Plan

1. 部署時在 `.env` 設定 `FORCE_HTTPS=true`
2. 確認反向代理有轉送 `X-Forwarded-Proto: https`
3. 無需停機，設定檔變更在下次請求即生效
4. 若出現問題，將 `FORCE_HTTPS` 改回 `false` 即可復原

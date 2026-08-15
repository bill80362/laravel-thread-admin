## Why

OAuth 綁定 Threads 帳號流程失敗，回調後帳號未建立。根本原因是 `ThreadsClient` 中 OAuth token 相關的三個端點使用了錯誤的 base URL（包含 `/v1.0` 前綴），導致 API 請求失敗。同時 `ThreadsOAuthController::callback()` 的 `catch (\Throwable $e)` 沒有記錄錯誤日誌，導致問題難以排查。

## What Changes

- 在 `ThreadsClient` 中新增 `OAUTH_BASE` 常數（不含 `/v1.0`），用於 OAuth token 端點
- 修改 `exchangeCodeForShortToken`、`exchangeShortForLongToken`、`refreshLongLivedToken` 使用 `OAUTH_BASE`
- 在 `ThreadsOAuthController::callback()` 的 catch 區塊中加入錯誤日誌記錄

## Capabilities

<!-- 純 bug fix，無規格層級行為變更。skip_specs: true -->

## Impact

- **修改檔案**：
  - `app/Services/ThreadsClient.php`（新增 OAUTH_BASE 常數，修改三個方法）
  - `app/Http/Controllers/ThreadsOAuthController.php`（加入錯誤日誌）

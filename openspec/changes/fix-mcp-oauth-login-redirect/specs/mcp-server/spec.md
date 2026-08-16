## ADDED Requirements

### Requirement: OAuth 授權流程未登入時重導向至後台登入頁
系統 SHALL 在使用者未登入而存取 HTTP 模式 MCP 的 OAuth 授權端點（`/oauth/authorize`）時，將使用者重導向至後台登入頁，而非回傳伺服器錯誤。

#### Scenario: 未登入存取 OAuth 授權端點
- **WHEN** 使用者尚未登入，且存取 OAuth 授權端點以開始授權流程
- **THEN** 系統 SHALL 將使用者重導向至後台登入頁
- **AND** 系統 SHALL 不回傳 `Route [login] not defined` 或其他伺服器錯誤

#### Scenario: 已登入存取 OAuth 授權端點
- **WHEN** 使用者已登入，且存取 OAuth 授權端點
- **THEN** 系統 SHALL 正常顯示授權確認頁，不進行登入重導向

### Requirement: MCP 端點認證失敗時回傳 JSON 錯誤
系統 SHALL 在 HTTP 模式 MCP 端點（`/mcp/*`）的 `auth:api` 認證失敗時，回傳 JSON 格式的 401 錯誤回應，而非 HTML 重導向。

#### Scenario: 未提供有效 token 呼叫 MCP 端點
- **WHEN** AI agent 未提供有效 Bearer token 而呼叫 MCP 端點
- **THEN** 系統 SHALL 回傳 JSON 格式的 401 Unauthenticated 回應
- **AND** 系統 SHALL 不回傳 HTML 重導向頁面

## Why

目前後台缺少一份面向非程式人員的「使用說明」，營運人員無法自行完成 Meta App 申請、帳號綁定、排程發文與回覆收集等操作；同時 MCP 服務（供 AI agent 串接）的 OAuth token 缺乏集中管理介面，無法查看或註銷當前登入使用者已授權的 token。

## What Changes

- 新增「使用說明」頁面（Filament Page），以步驟化、非程式語言的方式說明：
  - Meta App 申請流程（網址、取得「應用程式編號」與「應用程式密鑰」、加入 Threads 測試人員）。
  - 本系統的 Threads App 設定與帳號綁定。
  - 排程發文的狀態機流程與觸發頻率（每分鐘檢查、30 秒兩階段發佈、失敗重試）。
  - 回覆收集的偵測範圍（僅限本系統發出的貼文）與頻率（每 5 分鐘）。
  - MCP 服務的本地與 HTTP 兩種模式，以及 ChatGPT、Claude Desktop 的逐步註冊教學。
- 新增「MCP 控管」Resource（完整 Filament Resource，唯讀列表），列出當前登入使用者透過 OAuth 取得的 MCP token，顯示 Client 名稱、授權範圍、建立時間、到期時間與撤銷狀態，並提供「註銷」動作。
- 更新 `AGENTS.md`，補上「使用說明」與「MCP 控管」相關的開發規範。

## Capabilities

### New Capabilities

- `usage-guide`: 提供一份面向非程式人員的步驟化使用說明頁面，涵蓋 Meta App 申請、帳號綁定、排程發文、回覆收集與 MCP 服務設定。
- `mcp-token-management`: 提供 MCP OAuth token 的後台管理介面，可列出當前登入使用者的 token 並執行註銷。

### Modified Capabilities

（無）

## Impact

- **新增檔案**：`app/Filament/Pages/UsageGuide.php`、`resources/views/filament/pages/usage-guide.blade.php`、`app/Filament/Resources/McpTokens/McpTokenResource.php` 及相關頁面檔案。
- **修改檔案**：`AGENTS.md`（加入使用說明與 MCP 控管規範）。
- **依賴**：無新增（沿用 `filament/filament` 與 `laravel/passport`）。
- **資料模型**：直接使用 `Laravel\Passport\Token` 與 `Laravel\Passport\Client`，不新增資料表。
- **測試**：新增 MCP 控管 Resource 的 feature 測試（PHPUnit）。

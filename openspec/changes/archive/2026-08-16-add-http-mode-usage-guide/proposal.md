## Why

目前「使用說明」第五章（MCP 服務設定）只詳述了「本地模式」，對「HTTP 模式（遠端）」僅以一句話帶過。營運人員若要讓 Claude Desktop、ChatGPT 桌面版透過網路連線，缺少必要的網址、登入帳號、OAuth 授權流程與後續 token 管理等細節，無法獨立完成設定。

## What Changes

- 補全「MCP 服務設定」章節中 HTTP（遠端）模式的說明，涵蓋：
  - 對外連線網址（MCP 入口、`.well-known` metadata、OAuth 端點）與其來源（`APP_URL`）
  - 首次 OAuth 授權流程，並明確指出登入使用的是「後台登入帳號」
  - Claude Desktop 桌面版的遠端 HTTP 設定步驟與設定檔範例
  - ChatGPT 桌面版的遠端 HTTP 設定步驟
  - 授權後 token 在「MCP 控管」頁的檢視與註銷
  - 注意事項（對外網址必須可被連到、授權範圍 `mcp:use`）

## Capabilities

### New Capabilities

無。

### Modified Capabilities

- `usage-guide`: 擴充「使用說明涵蓋 MCP 服務設定」需求，補足 HTTP 模式的連線網址、登入帳號、OAuth 授權流程與 token 管理說明。

## Impact

- 修改檔案：`resources/views/filament/pages/usage-guide/chapter5.blade.php`
- 使用說明頁面文字內容更新，不涉及後端程式、資料庫 schema 或 API 變更。
- 無 breaking change。

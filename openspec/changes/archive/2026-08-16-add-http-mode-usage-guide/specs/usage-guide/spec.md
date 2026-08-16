## MODIFIED Requirements

### Requirement: 使用說明涵蓋 MCP 服務設定
使用說明 SHALL 說明 MCP 服務的本地與 HTTP 兩種模式，並提供 ChatGPT 與 Claude Desktop 的逐步註冊教學。HTTP 模式說明 SHALL 包含對外連線網址、OAuth 授權流程、登入帳號說明與 token 後續管理。

#### Scenario: 說明 MCP 兩種模式
- **WHEN** 使用者閱讀「MCP 服務」章節
- **THEN** 說明 SHALL 區分本地模式與 HTTP 模式的差異與適用情境
- **AND** 說明 SHALL 提供 ChatGPT 與 Claude Desktop 的逐步設定步驟

#### Scenario: 說明 HTTP 模式連線網址
- **WHEN** 使用者閱讀 HTTP 模式設定段落
- **THEN** 說明 SHALL 列出 MCP 入口網址（`/mcp/threads`）與 OAuth 相關端點（`.well-known` metadata、授權頁、token 端點）
- **AND** 說明 SHALL 解釋這些網址由 `APP_URL` 環境變數決定，對外網址必須是遠端 AI 客戶端可連到的公開網址

#### Scenario: 說明 OAuth 授權流程
- **WHEN** 使用者閱讀 HTTP 模式設定段落
- **THEN** 說明 SHALL 以步驟化方式描述 OAuth 2.1 授權流程（客戶端自動發現 → 動態註冊 → 跳轉授權頁 → 登入並允許 → 取得 token）
- **AND** 說明 SHALL 明確指出授權頁登入使用的是「後台登入帳號」（非 Threads 帳號或 Meta 帳號）

#### Scenario: 說明 Claude Desktop 遠端 HTTP 設定
- **WHEN** 使用者閱讀 Claude Desktop 設定段落
- **THEN** 說明 SHALL 提供設定檔路徑（macOS / Windows）
- **AND** 說明 SHALL 提供遠端 HTTP 模式的 JSON 設定範例（含 `type` 與 `url` 欄位）
- **AND** 說明 SHALL 說明首次連線時會自動跳出瀏覽器進行 OAuth 授權

#### Scenario: 說明 ChatGPT 桌面版遠端 HTTP 設定
- **WHEN** 使用者閱讀 ChatGPT 桌面版設定段落
- **THEN** 說明 SHALL 提供在 ChatGPT 桌面 App 中新增遠端 MCP 伺服器的操作步驟
- **AND** 說明 SHALL 說明儲存後會觸發 OAuth 授權流程

#### Scenario: 說明 token 後續管理
- **WHEN** 使用者完成 OAuth 授權後
- **THEN** 說明 SHALL 引導使用者至「MCP 控管」頁面查看已授權的 token
- **AND** 說明 SHALL 說明可在該頁面檢視 token 狀態、到期時間，以及手動註銷 token

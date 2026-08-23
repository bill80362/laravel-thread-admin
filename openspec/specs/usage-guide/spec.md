# usage-guide Specification

## Purpose

提供一份面向非程式人員的步驟化使用說明，涵蓋 Meta App 申請、帳號綁定、排程發文、回覆收集與 MCP 服務設定，降低營運人員的入門門檻。

## Requirements

### Requirement: 使用說明頁面可於後台存取
系統 SHALL 在後台左側導覽提供「使用說明」頁面，讓登入使用者得以查看完整操作說明。

#### Scenario: 進入使用說明頁面
- **WHEN** 登入使用者點擊左側導覽的「使用說明」
- **THEN** 系統 SHALL 顯示使用說明頁面，包含 Meta App 申請、帳號綁定、排程發文、回覆收集與 MCP 服務設定等章節

### Requirement: 使用說明涵蓋 Meta App 申請流程
使用說明 SHALL 以非程式人員可理解的方式，說明如何申請 Meta App、取得應用程式編號與應用程式密鑰，以及將要管理的 Threads 帳號加入測試人員。

#### Scenario: 說明申請步驟
- **WHEN** 使用者閱讀「前置準備」章節
- **THEN** 說明 SHALL 提供 Meta for Developers 網站網址
- **AND** 說明 SHALL 逐步說明建立應用程式、選擇 Threads use case、取得應用程式編號與密鑰、新增 Threads 測試人員的步驟

### Requirement: 使用說明涵蓋排程發文與回覆收集機制
使用說明 SHALL 以非程式人員可理解的方式，說明排程發文的狀態流程與觸發頻率，以及回覆收集的偵測範圍與頻率。

#### Scenario: 說明排程發文機制
- **WHEN** 使用者閱讀「排程發文」章節
- **THEN** 說明 SHALL 描述貼文的狀態流程（草稿、排程中、發佈中、已發佈、失敗）
- **AND** 說明 SHALL 說明系統每分鐘檢查到期貼文，以及發佈需先建立容器再等待約 30 秒的兩階段流程

#### Scenario: 說明回覆收集機制
- **WHEN** 使用者閱讀「回覆收集」章節
- **THEN** 說明 SHALL 說明系統僅偵測「本系統發出的貼文」的回覆
- **AND** 說明 SHALL 說明每 2 分鐘自動偵測一次新回覆

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

## Context

目前 `chapter5.blade.php` 已包含 MCP 服務設定的本地模式說明（Claude Desktop 與 ChatGPT 的 Stdio 設定），但 HTTP 模式僅以一句「如果需要遠端（HTTP）模式連線，請先在『MCP 控管』頁面管理 OAuth token」帶過。需在此檔案中補全 HTTP 模式的完整說明。

現有章節結構（`UsageGuide.php`）已包含「五、MCP 服務設定（AI 工具串接）」Section，直接擴充其對應的 Blade view 即可，不需新增 Section。

## Goals / Non-Goals

**Goals:**
- 在 `chapter5.blade.php` 中補全 HTTP 模式的完整設定說明
- 涵蓋 Claude Desktop 桌面版與 ChatGPT 桌面版的遠端 HTTP 設定步驟
- 說明 OAuth 授權流程、登入帳號、對外網址與 token 管理

**Non-Goals:**
- 不修改 `UsageGuide.php` 的 Section 結構
- 不修改後端程式、路由或設定檔
- 不新增 `config/mcp.php`（目前 vendor 預設值已滿足需求）

## Decisions

### 1. 內容組織方式：在現有 chapter5 中擴充，而非新增 chapter6

**理由**：現有 chapter5 已涵蓋 MCP 服務設定主題，HTTP 模式是同一主題的延伸。拆成兩個 chapter 反而讓使用者需要在兩個頁面間跳轉。

### 2. 設定範例格式：Claude Desktop 使用 `type: "http"` + `url` 格式

**理由**：根據 Claude Code 官方文件，遠端 HTTP 伺服器使用 `"type": "http"` 搭配 `"url"` 欄位。Claude Desktop 桌面版與 Claude Code 共用相同的 `claude_desktop_config.json` 設定檔格式。

### 3. 不提供預先申請 client_id 的設定方式

**理由**：本系統支援動態客戶端註冊（`POST /oauth/register`），客戶端無需預先持有 `client_id`。這簡化了使用者的設定步驟，說明中只需提供 MCP 入口 URL 即可。

### 4. 流程圖使用純文字 ASCII 圖

**理由**：OAuth 流程涉及多個參與者（AI 客戶端、後台、瀏覽器、使用者），文字描述較難理解。專案前端（`package.json`）未載入 Mermaid 依賴，若使用 Mermaid 語法將只顯示原始語法、對非程式人員不友善。改用等寬字型的 ASCII 流程圖，無需額外依賴即可直接渲染，且同時保留純文字步驟說明。

## Risks / Trade-offs

- **Claude Desktop / ChatGPT 桌面版 UI 可能隨版本變動**：設定步驟中的選單名稱（如「連接的應用程式」vs「MCP 伺服器」）可能因版本而異。→ 說明中標註「以實際 App 版本為準」。
- **ASCII 流程圖在不同字型下對齊可能略有偏移**：使用 `<pre>` 與等寬字型（`leading-relaxed`）呈現，確保對齊可讀。

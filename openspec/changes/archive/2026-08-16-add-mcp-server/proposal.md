## Why

目前所有操作（發文、查看回覆、回覆留言）都只能透過 Filament 後台介面手動完成，無法讓 AI agent 或其他外部程式以程式化方式串接。透過 Model Context Protocol (MCP) 暴露服務，可讓 AI 編輯器、Claude Desktop、或其他 MCP client 直接讀取帳號、建立排程貼文、查詢貼文與回覆，提升自動化與擴充能力。

## What Changes

- 新增 MCP Server（`ThreadsMcpServer`），同時以**本地（local，Artisan command）**與 **HTTP（web）** 兩種方式註冊。
- 提供六個 MCP Tools：
  - `list-accounts`：列出可用（發文／回覆）的 Threads 帳號。
  - `create-post`：建立排程貼文。
  - `list-posts`：查詢貼文清單（支援依帳號、狀態篩選）。
  - `get-post`：查詢單一貼文詳細資訊。
  - `list-replies`：查詢回覆清單（支援依帳號、貼文、狀態篩選）。
  - `create-reply`：建立一筆手動回覆記錄。
- 抽取共用業務邏輯層：新增 `PostService` 與 `ReplyService`（部分抽取），供 MCP Tools 使用，未來 Filament 亦可漸進收斂到同一 Service。
- HTTP 模式使用 Laravel Passport OAuth（`auth:api`）保護，透過 `Mcp::oauthRoutes()` 註冊 OAuth2 discovery 與 client registration 路由。
- **不包含**帳號綁定（OAuth 授權）相關操作：綁定仍僅限於 Filament 介面完成；MCP 只讀取已綁定帳號。
- 更新專案 `AGENTS.md`，加入 MCP 開發規範、目錄結構、認證方式與「業務邏輯需與介面整合」的約定。

## Capabilities

### New Capabilities
- `mcp-server`: 提供 MCP 伺服器與六個工具，讓外部 AI agent 得以讀取帳號、建立排程貼文、查詢貼文與回覆、建立手動回覆記錄。

### Modified Capabilities
（無）

## Impact

- **新增檔案**：`routes/ai.php`、`app/Mcp/Servers/ThreadsMcpServer.php`、`app/Mcp/Tools/*`（六個 tool）、`app/Services/PostService.php`、`app/Services/ReplyService.php`。
- **修改檔案**：`AGENTS.md`（加入 MCP 規範）、`app/Providers/AppServiceProvider.php`（Passport authorizationView，如需）。
- **依賴**：`laravel/mcp`、`laravel/passport`（皆已安裝）。
- **測試**：新增 MCP server/tool 的單元或 feature 測試（PHPUnit）。

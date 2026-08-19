## REMOVED Requirements

### Requirement: MCP 伺服器以本地與 HTTP 兩種方式提供
**Reason**: 本地（Artisan）模式下 `auth()->id()` 為 null，無法進行資料隔離，已無實際用途。
**Migration**: `routes/ai.php` 移除 `Mcp::local('threads', ThreadsMcpServer::class)`，僅保留 HTTP 模式。

#### Scenario: 本地方式啟動伺服器（已移除）
- **WHEN** AI agent 嘗試以本地方式啟動 MCP 伺服器
- **THEN** 系統 SHALL 不再提供本地 Artisan command 模式

## ADDED Requirements

### Requirement: MCP Threads 帳號僅可讀取
系統 SHALL 在 MCP 中僅提供 Threads 帳號的讀取功能（`list-accounts`），不提供新增、修改或刪除帳號的工具。

#### Scenario: MCP 不包含帳號管理工具
- **WHEN** AI agent 查詢 MCP 伺服器提供的工具清單
- **THEN** 工具清單 SHALL 包含 `list-accounts`（唯讀）
- **AND** 工具清單 SHALL 不包含任何帳號新增、修改或刪除的工具

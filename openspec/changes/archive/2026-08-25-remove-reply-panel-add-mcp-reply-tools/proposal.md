## Why

回覆面板（`/user/replies`）的列表閱讀功能已被貼文列表的抽屜回覆取代，實際使用上抽屜更直覺方便。同時 MCP 工具目前僅能對「貼文」回覆（`CreateReplyTool`），缺少對「單則留言」回應及管理回覆狀態的能力，需要補強。

## What Changes

- **移除回覆面板**（`ReplyResource` 及其所有相關檔案）：不再需要獨立的回覆列表頁面，回覆閱讀直接透過貼文抽屜完成。
- **新增 `ReplyToReplyTool`**：MCP 工具，對指定回覆留言（reply）發出回應，呼叫現有 `ReplyService::publish()`。
- **新增 `UpdateReplyStatusTool`**：MCP 工具，變更回覆狀態（如標記為已忽略、已讀）。
- **MCP Server 註冊**：將兩個新工具加入 `ThreadsMcpServer` 的 `$tools` 陣列。
- **使用說明更新**：移除回覆面板相關說明文字。

## Capabilities

### New Capabilities
- `mcp-reply-to-reply`: MCP 工具，對指定回覆留言發出回應（非對貼文回覆）
- `mcp-reply-status`: MCP 工具，變更回覆的狀態（已讀、忽略）

### Modified Capabilities
- （無）回覆收集與顯示的行為不變，僅移除獨立列表頁面

## Impact

- **移除檔案**：`app/Filament/Resources/Replies/` 整個目錄（含 Pages、Schemas、Tables、Widgets）、`resources/views/filament/widgets/replies-sync-notice.blade.php`、`tests/Feature/ReplyResourceTest.php`
- **新增檔案**：`app/Mcp/Tools/ReplyToReplyTool.php`、`app/Mcp/Tools/UpdateReplyStatusTool.php`
- **修改檔案**：`app/Mcp/Servers/ThreadsMcpServer.php`（註冊新工具）、`resources/views/filament/pages/usage-guide/chapter2.blade.php`（更新說明文字）
- 無資料庫 migration 變更
- 無新增依賴

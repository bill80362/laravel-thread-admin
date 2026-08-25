## Context

See proposal.md - Why and What Changes for motivation and scope.

現有架構：
- `ReplyResource`（`app/Filament/Resources/Replies/`）是 Filament 資源，提供獨立回覆列表頁面（`/user/replies`），包含列表、篩選、新增、回應動作、同步提示 Widget。
- `ReplyService` 已封裝了 `createPostReply()`（對貼文回覆）、`publish()`（對留言回應）、`markAsRead()`、`markAllAsRead()` 等完整方法。
- MCP 已有 `ListRepliesTool` 和 `CreateReplyTool`，分別對應查詢回覆和對貼文建立回覆。

## Goals / Non-Goals

**Goals:**
- 移除回覆面板（`ReplyResource` 及其所有子檔案），不再需要獨立頁面
- 新增 `ReplyToReplyTool`：MCP 工具，對指定留言回應，呼叫 `ReplyService::publish()`
- 新增 `UpdateReplyStatusTool`：MCP 工具，變更回覆狀態（已讀、忽略、已回覆）
- 更新使用說明，移除回覆面板相關文字

**Non-Goals:**
- 不修改 `ReplyService` 現有邏輯（兩個新工具直接使用現有 Service 方法）
- 不修改資料庫結構
- 不修改回覆收集機制（`CollectThreadsReplies`、`ThreadsWebhookService`）

## Decisions

### D1: ReplyToReplyTool 使用 ReplyService::publish()

`ReplyService::publish(Reply $reply, string $text)` 已封裝對留言回應的完整流程（建立 container → 排程發佈），新工具直接注入 Service 呼叫此方法即可。

**替代方案**：在 `ReplyService` 新增專門方法 → 不需要，既有方法已符合需求。

### D2: UpdateReplyStatusTool 直接更新 Reply Model

狀態變更邏輯單純（更新 `status`、選擇性更新 `read_at`/`replied_at`），直接在 Tool 的 `handle()` 中操作 Model，不另外開 Service 方法。

### D3: UpdateReplyStatusTool 的 status 參數使用字串而非 enum

MCP 工具參數為 JSON 序列化，使用字串 `"read"`、`"ignored"`、`"replied"` 更直覺，內部再轉換為 `ReplyStatus` enum。

### D4: 回覆面板直接刪除，不標記 deprecated

回覆面板的讀取功能已完全被貼文抽屜取代，且無其他外部依賴，直接移除整個 `Replies/` 目錄。

## Risks / Trade-offs

- [移除後無法從單一頁面總覽所有回覆] → 保留貼文卡片上的「回覆」按鈕（含未讀/總數徽章），可從貼文列表進入各貼文的回覆抽屜。MCP 也有 `ListRepliesTool` 可查詢。
- [使用說明 chapter2 需更新] → 移除「回覆面板」相關文字，改描述抽屜回覆與 MCP 回覆工具。

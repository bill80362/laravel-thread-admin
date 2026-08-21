## Context

排程發文列表（`ListPosts`）目前以卡片式（`contentGrid`）呈現，`recordActions` 僅有「編輯」「刪除」。回覆資料由 `CollectThreadsReplies` Job 每 5 分鐘輪詢寫入 `replies` 表，`Reply` model 已有 `post_id`、`author_username`、`text`、`source`、`status`、`replied_at` 等欄位。回覆發佈已由 `ReplyService::createPostReply()` + `PublishReply` Job 實作，MCP 工具（`CreateReplyTool`、`ListRepliesTool`）也共用此 Service。專案目前沒有自訂 Livewire 元件。Filament 版本為 v5.7.6。

## Goals / Non-Goals

**Goals:**
- 在貼文卡片提供「回覆」按鈕與「有新回覆」警示。
- 以自訂 Blade + Livewire 實作右側抽屜，AJAX 讀取回覆串，排版貼近 Threads。
- 回覆串由舊到新排列，開啟時自動捲動到最新。
- 抽屜內可回覆貼文，與現有 `ReplyService` 整合。
- 新增 `read_at` 欄位管理已讀/未讀狀態。

**Non-Goals:**
- 不修改回覆列表頁（`/admin/replies`）的既有功能。
- 不改變 MCP 工具的既有行為（僅共用 Service）。
- 不實作即時推送（WebSocket），回覆仍依輪詢更新。

## Decisions

### D1: 已讀狀態使用 `read_at` 欄位而非重用 `status`
`Reply.status` 代表發佈狀態（new/publishing/replied/failed/ignored），與「使用者是否看過」是不同維度。新增 `read_at`（datetime，null = 未讀）欄位，語意清楚且不與既有狀態混淆。
- **替代方案**：重用 `status` 加 read/unread —— 會與發佈狀態衝突，捨棄。

### D2: 抽屜以自訂 Blade + Livewire 實作，而非 Filament 內建 Drawer
需求要求 Threads 風格排版、AJAX 讀取、自動捲動，Filament 內建 Drawer 元件排版受限。改用自訂 Livewire 元件（`app/Livewire/PostReplyDrawer.php`）+ Blade view，由 `ListPosts` 頁面透過 Alpine.js 觸發開啟。
- **替代方案**：Filament `Drawer` schema 元件 —— 排版彈性不足，捨棄。

### D3: 抽屜回覆與 MCP 共用 `ReplyService`
抽屜的「回覆貼文」呼叫 `ReplyService::createPostReply()`，與 MCP `CreateReplyTool` 行為一致；回覆清單讀取沿用 `ReplyService::list()`。確保功能整合一致性。
- **替代方案**：抽屜內獨立實作回覆邏輯 —— 會造成行為分歧，捨棄。

### D4: 回覆串排序與捲動
回覆串以 `created_at` 升序（舊 → 新）排列，最新在最下方。Livewire 載入回覆後，透過 Alpine.js 在 DOM 更新後將捲動容器 `scrollTop` 設為 `scrollHeight`，自動捲到最新。送出回覆後同樣捲動到最新。

### D5: 警示與已讀標記
「有新回覆」警示依貼文的未讀回覆數（`read_at IS NULL`）決定。開啟抽屜時，將該貼文所有回覆 `read_at` 設為 `now()`，警示隨之消失。`CollectThreadsReplies` 建立新回覆時 `read_at` 保持 null（未讀）。

## Risks / Trade-offs

- [自動捲動可能干擾使用者查看舊回覆] → 僅在開啟抽屜與送出回覆後觸發一次捲動，之後不干擾。
- [大量回覆時一次載入可能較慢] → 抽屜僅載入單一貼文的回覆，數量有限；若未來需要再分頁。
- [`read_at` backfill 需在 migration 中處理既有資料] → migration 將既有回覆 `read_at` 設為 `now()`（已讀）。

## Migration Plan

1. 新增 migration：`replies` 表加 `read_at`（datetime, nullable），並 backfill 既有回覆為 `now()`。
2. 部署後既有回覆視為已讀，新輪詢回覆為未讀。
3. 回滾：drop `read_at` 欄位即可，無資料遺失風險。

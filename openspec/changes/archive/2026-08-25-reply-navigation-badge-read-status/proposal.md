## Why

目前貼文卡片的「有新回覆」badge 雖然有提示功能，但管理者需逐一瀏覽貼文才知道有新回覆。側邊欄「回覆面板」沒有任何未讀提示，且 MCP 客戶端查詢貼文/回覆時也無法得知回覆的新舊狀態。需要一個全域性的未讀回覆提醒機制，讓管理者與 MCP 客戶端都能快速掌握新回覆狀況。

## What Changes

- **側邊欄 Navigation Badge**：在「回覆面板」選單旁顯示 `${未讀數}/${總數}` badge，點擊進入後自動標記為已讀
- **MCP ListPostsTool**：回傳欄位新增 `reply_unread_count`、`reply_total_count`
- **MCP ListRepliesTool**：查詢回覆時新增 `read_at` 欄位回傳；查詢後可選擇標記已讀
- **ReplyService**：新增 `unreadTotalCount()`（全域未讀）、`totalCount()`（總數）、以及以 post_id 為條件的 `markAsRead()`（MCP 版）

## Capabilities

### New Capabilities
- `reply-navigation-badge`: 側邊導覽列回覆面板的未讀/總數 badge 與點擊標記已讀
- `mcp-reply-read-status`: MCP 工具的未讀回覆數量與標記已讀能力

### Modified Capabilities
- `reply-read-status`: 新增全域未讀回覆統計能力，既有已讀/未讀行為不變
- `replies-sync-notice`: 無 spec 層級行為變更，不需修改

## Impact

- `app/Filament/Resources/Replies/ReplyResource.php` — 新增 `getNavigationBadge()`
- `app/Filament/Resources/Replies/Pages/ListReplies.php` — 進入時標記已讀
- `app/Services/ReplyService.php` — 新增 `unreadTotalCount()`、`totalCount()`
- `app/Mcp/Tools/ListPostsTool.php` — 回傳回覆數量
- `app/Mcp/Tools/ListRepliesTool.php` — 回傳 `read_at` 欄位，支援查詢後標記已讀

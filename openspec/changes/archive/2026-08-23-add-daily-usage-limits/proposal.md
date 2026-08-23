## Why

目前 User 表雖有 `max_daily_posts` 和 `max_daily_replies` 欄位，但沒有任何程式碼在發送前檢查這些限制，導致限制形同虛設。同時刪除貼文後本地記錄會消失，無法準確計算當日用量。需要一套完整的每日用量限制機制，包含發送前檢查、用量記錄、軟性警告與管理查詢。

## What Changes

- **新增 `activity_logs` 表**：記錄每次發送貼文/回覆的紀錄，刪除貼文不影響計數
- **發送前硬性阻擋**：`PublishScheduledPost` 和 `PublishReply` 在建立 container 前檢查當日用量，超額則標記 Failed
- **MCP 軟性警告**：`CreatePostTool` 和 `CreateReplyTool` 建立時回傳用量警告
- **User 列表頁用量提示條**：貼文列表頂部顯示今日發文/回覆用量條
- **Admin 用量明細查詢**：User 列表的「今日發文」「今日回覆」可點擊，右側抽屜顯示明細
- **User 用量明細查詢**：User 端新增「發送紀錄」導航頁面，可查看自己的發送明細

## Capabilities

### New Capabilities
- `daily-usage-limits`: 每日發文/回覆用量限制的記錄、檢查與查詢

### Modified Capabilities
- （無現有 spec 需要修改）

## Impact

- `database/migrations/`：新增 `activity_logs` 表 migration
- `app/Models/`：新增 `ActivityLog` Model
- `app/Jobs/`：修改 `PublishScheduledPost`、`PublishReply`（寫入 log + 發送前檢查）
- `app/Mcp/Tools/`：修改 `CreatePostTool`、`CreateReplyTool`（回傳軟性警告）
- `app/Filament/Resources/Posts/Pages/ListPosts`：新增頂部用量提示條
- `app/Filament/Resources/Users/Tables/UsersTable`：用量欄位改為可點擊
- `app/Filament/Resources/`：新增 `ActivityLogs/` 資源（User 端）
- `app/Filament/Resources/Users/RelationManagers/`：新增用量明細 RelationManager（Admin 端）

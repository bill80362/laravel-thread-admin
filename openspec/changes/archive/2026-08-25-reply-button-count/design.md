## Context

目前貼文列表（`ListPosts`）使用 Filament 的 `recordActions()` 建立「回覆」按鈕，按鈕標籤為固定文字。側邊導覽列（`ReplyResource`）透過 `getNavigationBadge()` 顯示全體 `未讀/總數`。

此變更非常單純：移除側邊欄 badge，並讓按鈕標籤動態計算每篇貼文的未讀/總回覆數。

## Goals / Non-Goals

**Goals:**
- 移除 `ReplyResource::getNavigationBadge()` 及對應的 color 方法
- 將「回覆」按鈕標籤改為 `回覆 (<unreadCount>/<totalCount>)` 格式，總數為 0 時僅顯示 `回覆`

**Non-Goals:**
- 不修改回覆抽屜的行為或樣式
- 不修改資料庫結構
- 不更動 `ReplyService` 的既有方法簽章
- 不影響「有新回覆」警示 badge（保留現有行為）

## Decisions

### Decision: 使用 closure 動態產生按鈕標籤
- **選擇**: 在 `ListPosts.php` 的 `viewReplies` action 中將 `->label()` 改為 `->label(fn (Post $record): string => ...)`，直接使用 `ReplyService` 查詢
- **原因**: 變更範圍最小，無需新增 Model attribute 或 accessor，即時反映資料庫最新狀態
- **替代方案**: 在 `Post` model 上新增 `->withCount('replies', 'replies as unread_replies_count' => ...)` eager load。不採用是因為變動較大、且與現有寫法不一致（現有已使用 `ReplyService`）

### Decision: 無需修改 ReplyService
- `ReplyService::unreadCount($postId)` 與 `ReplyService::totalCountForPost($postId)` 已完全滿足需求
- 不需要新增方法

### Decision: 不將側邊欄 badge 改為其他功能，直接移除
- badge 上的資料（全域未讀/總數）在貼文卡片按鈕上以單篇維度呈現更具操作性

## Risks / Trade-offs

- **[效能]** 每張卡片渲染時會執行兩次 SQL COUNT 查詢。因貼文列表最多 10~50 筆，且 `replies` 表有 `user_id + post_id` 索引，影響可忽略。
- **[UX 變更]** 管理者習慣從側邊欄 badge 得知全域未讀數，移除後需從貼文列表逐一查看。作為補償，每張卡片的按鈕直接顯示該貼文的未讀數，資訊更具體且可操作。

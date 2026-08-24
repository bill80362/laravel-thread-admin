## Context

目前系統已有：
- `ReplyService::unreadCount(int $postId)` — 計算單篇貼文的未讀回覆
- `ReplyService::markAsRead(int $postId)` — 將單篇貼文的回覆標記已讀
- `PostReplyDrawer::mount()` — 開啟抽屜時調用 `markAsRead`
- 貼文卡片已有「有新回覆」badge（`ListPosts` 中的 `unread_badge` TextColumn）

需要新增：
- 全域統計方法（不分貼文的未讀總數與總數）
- 側邊欄 navigation badge（Filament Resource 層級）
- 進入 `ListReplies` 頁面時自動標記全域已讀
- MCP 工具的回覆數量與已讀狀態

## Goals / Non-Goals

**Goals:**
- 側邊欄「回覆面板」旁顯示 `${未讀}/${總數}` badge
- 進入回覆面板頁面時自動將所有未讀標記為已讀
- MCP `list_posts` 回傳每篇貼文的 `reply_unread_count`、`reply_total_count`
- MCP `list_replies` 回傳 `read_at` 欄位，支援 `mark_as_read` 參數

**Non-Goals:**
- 即時推送通知（Toast / Browser Notification）— 留待後續
- 修改貼文卡片既有「有新回覆」badge 行為
- 修改資料庫 schema — 全部使用現有欄位

## Decisions

### Decision 1: ReplyService 新增全域統計方法

在 `ReplyService` 新增兩個方法：

```php
// 查詢該使用者的未讀回覆總數（跨所有貼文）
public function unreadTotalCount(?int $userId = null): int
{
    $userId ??= auth()->id();
    return Reply::query()->where('user_id', $userId)->whereNull('read_at')->count();
}

// 查詢該使用者的回覆總數
public function totalCount(?int $userId = null): int
{
    $userId ??= auth()->id();
    return Reply::query()->where('user_id', $userId)->count();
}

// 將該使用者的所有未讀回覆標記已讀
public function markAllAsRead(?int $userId = null): int
{
    $userId ??= auth()->id();
    return Reply::query()->where('user_id', $userId)->whereNull('read_at')
        ->update(['read_at' => now()]);
}
```

**Rationale**: 統一到 `ReplyService` 而非散布在各工具/頁面，與既有 `unreadCount()`、`markAsRead()` 一致。

### Decision 2: ReplyResource 側邊欄 badge

Filament Resource 支援 `getNavigationBadge()` 靜態方法：

```php
public static function getNavigationBadge(): ?string
{
    $unread = app(ReplyService::class)->unreadTotalCount();
    $total = app(ReplyService::class)->totalCount();

    return "{$unread}/{$total}";
}
```

**Rationale**: Filament 內建機制，無需自訂 Blade 或 JavaScript。每次頁面載入時重新計算，確保 badge 反映最新狀態。

### Decision 3: 進入回覆面板自動標記已讀

在 `ListReplies` Page 的 `mount()` 或 `boot()` 中調用 `markAllAsRead()`：

```php
public function mount(): void
{
    parent::mount();
    app(ReplyService::class)->markAllAsRead();
}
```

**Rationale**: 
- `ListReplies` 是回覆面板的入口頁面，管理者點擊側邊欄「回覆面板」時最先抵達此頁
- `mount()` 在頁面渲染前執行，確保 badge 在頁面載入時已更新
- Filament 的 navigation badge 會在頁面切換時重新計算，所以 badge 會自動更新為 `0/總數`

### Decision 4: MCP 工具的修改

**ListPostsTool**：

PostService 的 `list()` 已載入關聯，可在 map 中附加統計：

```php
$result = $posts->list($data)->map(fn ($post): array => [
    // ... 既有欄位
    'reply_unread_count' => app(ReplyService::class)->unreadCount($post->id),
    'reply_total_count' => $post->replies()->count(),
]);
```

**ListRepliesTool**：

在回傳結果中加入 `read_at` 欄位，並檢查 `mark_as_read` 參數：

```php
$data = $request->validate([
    // ... 既有欄位
    'mark_as_read' => ['nullable', 'boolean'],
]);

$result = $replies->list($data)->map(fn ($reply): array => [
    // ... 既有欄位
    'read_at' => $reply->read_at,
]);

if ($data['mark_as_read'] ?? false) {
    $replies->markAllAsRead();
}
```

**Rationale**: 
- 不修改 `ReplyService::list()` 的行為，避免影響既有呼叫端
- `markAllAsRead()` 作用於整個使用者而非單一貼文，因為 MCP 情境下客戶端可能一次查詢多篇貼文的回覆

### Decision 5: ReplyService 新增 totalCountForPost 方法

為避免 `ListPostsTool` 直接操作 Model，`ReplyService` 也補上單篇貼文的總數方法：

```php
public function totalCountForPost(int $postId, ?int $userId = null): int
{
    $userId ??= auth()->id();
    return Reply::query()->where('user_id', $userId)->where('post_id', $postId)->count();
}
```

## Risks / Trade-offs

- **效能**：`getNavigationBadge()` 每次頁面載入時執行兩次 COUNT 查詢。考量 replies 表規模不大（每使用者數百至數千筆），對 MySQL 而言 COUNT 是廉價操作，暫不需快取。
- **MCP 已讀標記範圍**：`mark_as_read = true` 會將該使用者「所有」未讀回覆標為已讀，而非僅限查詢結果中的貼文。這是最簡單的實作，且符合「客戶端要讀取回覆 = 已讀」的直覺。若有精細需求可後續調整。
- **側邊欄 badge 非即時更新**：Filament navigation badge 僅在頁面載入時重新計算。若使用者在回覆面板頁面停留期間有新回覆，badge 不會自動更新。可接受，因為回覆是每 2 分鐘輪詢，使用者回到其他頁面時 badge 就會更新。

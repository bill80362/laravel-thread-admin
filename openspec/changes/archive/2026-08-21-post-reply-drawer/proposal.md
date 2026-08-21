## Why

目前排程發文列表僅能查看貼文本身，無法直接查看該貼文在 Threads 上的回覆串，也無法得知是否有新回覆。管理者必須切換到回覆列表才能查看，且無法快速回覆貼文。需要一個貼近 Threads 體驗的抽屜式回覆檢視，並能標記已讀/未讀，方便管理者掌握最新互動。

## What Changes

- 在排程發文列表的每張貼文卡片上，於「刪除」旁新增「回覆」按鈕。
- 貼文有未讀回覆時，卡片顯示「有新回覆」警示 badge。
- 點擊「回覆」按鈕後，右側展開抽屜（自訂 Blade + Livewire），以 AJAX 讀取該貼文的所有回覆。
- 抽屜內回覆串由上而下為舊 → 新（與 Threads 一致），開啟時自動捲動到最新回覆。
- 抽屜下方提供回覆輸入框，可回覆該貼文（與現有 `ReplyService` 整合，真正發佈到 Threads）。
- `replies` 表新增 `read_at` 欄位（datetime，null = 未讀）：
  - 現有回覆 backfill 為已讀。
  - 之後輪詢抓回來的回覆標記為未讀。
  - 點擊抽屜後將該貼文所有回覆標記為已讀。

## Capabilities

### New Capabilities

- `post-reply-drawer`: 排程發文列表的貼文回覆抽屜，包含「回覆」按鈕、「有新回覆」警示、Threads 風格回覆串檢視、自動捲動到最新、以及抽屜內回覆貼文功能。
- `reply-read-status`: 回覆的已讀/未讀狀態管理，包含 `read_at` 欄位、輪詢新回覆標記未讀、開啟抽屜標記已讀。

### Modified Capabilities

- `replies-sync-notice`: 回覆輪詢（`CollectThreadsReplies`）建立新回覆時，需將 `read_at` 設為 null（未讀），與已讀狀態整合。

## Impact

- **資料庫**：`replies` 表新增 `read_at` 欄位（migration + backfill）。
- **Model**：`Reply` 新增 `read_at` cast 與 fillable。
- **Service**：`ReplyService` 新增 `markAsRead()`、`unreadCount()` 等方法。
- **Job**：`CollectThreadsReplies` 建立回覆時設定 `read_at = null`。
- **Filament**：`ListPosts` 新增「回覆」按鈕與「有新回覆」警示；新增 Livewire 元件與 Blade view 實作抽屜。
- **前端**：新增抽屜的 AJAX 讀取、Threads 風格排版、自動捲動邏輯。

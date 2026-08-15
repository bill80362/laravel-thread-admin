## Context

回覆資源（`ReplyResource`）目前僅有列表頁（`ListReplies`），表單 schema（`ReplyForm`）為空。回覆的設計初衷是透過 `CollectThreadsReplies` Job 自動從 Threads API 收集，但管理者仍有手動新增回覆記錄的需求（例如：離線記錄、測試資料、非 Threads 來源的回覆）。

`Reply` 模型的欄位：
- `threads_account_id` — 必填，關聯 Threads 帳號
- `post_id` — 可選，關聯貼文
- `threads_reply_id` — Threads API 的 reply ID（手動新增時無值）
- `author_username` — 留言者名稱
- `text` — 留言內容
- `source` — 來源（polling / webhook / manual）
- `status` — 狀態（new / replied / ignored）
- `replied_at` — 回覆時間（手動新增時無值）

## Goals / Non-Goals

**Goals:**
- 補齊 `ReplyForm` 表單欄位，讓手動新增回覆有完整的輸入介面。
- 新增 `CreateReply` 頁面，支援獨立的新增頁面路由。
- 手動新增時自動設定 `source = ReplySource::Manual`、`status = ReplyStatus::New`。
- 不影響現有列表頁的表格、篩選器、操作按鈕。

**Non-Goals:**
- 不新增 Edit 頁面（回覆編輯不在本次範圍）。
- 不改變 `CollectThreadsReplies` 的自動收集邏輯。
- 不改變列表頁的「回覆」和「忽略」操作按鈕。

## Decisions

### Decision 1: ReplySource 新增 Manual case
- **做法**：在 `ReplySource` enum 新增 `case Manual = 'manual'`。
- **理由**：區分手動建立與自動收集的回覆，便於後續過濾或審計。
- **替代方案**：直接沿用 `polling`。缺點是無法區分來源，未來若需統計或過濾會混淆。

### Decision 2: 使用獨立 CreateReply 頁面（非 modal）
- **做法**：新增 `CreateReply` extends `CreateRecord`，在 `ReplyResource::getPages()` 註冊 `'create' => CreateReply::route('/create')`。
- **理由**：與 `PostResource` 的 `CreatePost` 頁面模式一致，保持專案慣例。獨立頁面比 modal 更適合有多個欄位的表單。
- **替代方案**：在 `ListReplies` 使用 `CreateAction` 的 modal form。缺點是 modal 空間有限，且與 Post 的建立方式不一致。

### Decision 3: 表單欄位設計
- **做法**：
  - `threads_account_id`：`Select`，關聯 `threadsAccount`，必填
  - `post_id`：`Select`，關聯 `post`，可選（nullable）
  - `author_username`：`TextInput`，必填，prefix `@`
  - `text`：`Textarea`，必填，max 500
  - 不顯示 `source`、`status`、`threads_reply_id`、`replied_at`（由系統自動設定）
- **理由**：`source` 和 `status` 由 `mutateFormDataBeforeCreate` 自動設定，不需暴露給使用者；`threads_reply_id` 和 `replied_at` 對手動新增無意義。
- **替代方案**：顯示 `status` 下拉讓使用者可選。缺點是增加不必要的複雜度，手動新增的回覆預設就是「未回覆」。

### Decision 4: mutateFormDataBeforeCreate 自動設定
- **做法**：在 `CreateReply::mutateFormDataBeforeCreate()` 中自動注入 `source = ReplySource::Manual->value` 和 `status = ReplyStatus::New->value`。
- **理由**：與 `CreatePost` 自動設定 `status = PostStatus::Scheduled` 的模式一致。

### Decision 5: 將 threads_reply_id 改為可為 null
- **做法**：新增 migration `2026_08_15_142945_make_threads_reply_id_nullable_on_replies_table`，將 `threads_reply_id` 從 NOT NULL 改為 nullable（SQLite 不支援 ALTER COLUMN，採用重建資料表方式）。
- **理由**：手動新增的回覆沒有 Threads API 的 reply ID，但資料庫欄位原為 NOT NULL，導致手動建立回覆時觸發 NOT NULL constraint violation。讓該欄位 nullable 才能同時支援「自動收集（有 API ID）」與「手動建立（無 API ID）」兩種來源。
- **替代方案**：手動建立時填入佔位值（如空字串）。缺點是 `threads_reply_id` 具 unique 索引，多筆手動回覆會因空字串重複而違反唯一限制，且語意不正確。

## Risks / Trade-offs

- **[post_id 下拉選項過多]** → 若貼文數量成長，下拉選單可能過長。目前貼文數量少，暫不處理；未來可考慮加上搜尋功能（`->searchable()`）或改用 `SelectFilter` 式的關聯選擇器。
- **[手動新增的回覆無法與 API 回覆關聯]** → `threads_reply_id` 為空，無法透過 API 回覆此留言。這是預期行為，手動新增僅供記錄用途。

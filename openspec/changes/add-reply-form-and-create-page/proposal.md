## Why

回覆面板（`admin/replies`）目前有兩個問題：

1. **新增回覆的 form 是空的** — `ReplyForm::configure()` 的 `components()` 為空陣列，點擊「新增回覆」按鈕會跳出沒有任何欄位的 modal，無法手動建立回覆。
2. **缺少 CreateReply 頁面** — `ReplyResource::getPages()` 僅註冊了 `index`，沒有 `create` 路由，但 `ListReplies` 卻有 `CreateAction::make()`，導致點擊新增時行為異常。

此外，`ReplySource` enum 目前只有 `polling` 和 `webhook` 兩個值，手動新增的回覆需要一個新的來源標記。

## What Changes

- 在 `ReplySource` enum 新增 `Manual` 選項，標記手動建立的回覆。
- 補齊 `ReplyForm` 的表單欄位：來源帳號、所屬貼文（可選）、留言者、留言內容、狀態。
- 新增 `CreateReply` 頁面，並在 `ReplyResource::getPages()` 註冊 `create` 路由。
- 手動新增時自動設定 `source = ReplySource::Manual`、`status = ReplyStatus::New`。
- 將 `threads_reply_id` 欄位改為可為 null，支援無 API ID 的手動回覆。

## Capabilities

### New Capabilities
- `reply-manual-create`：管理者可手動新增回覆記錄

### Modified Capabilities
- `reply-management`：回覆面板新增手動建立功能

## Impact

- `app/Enums/ReplySource.php` — 新增 `Manual` case
- `app/Filament/Resources/Replies/Schemas/ReplyForm.php` — 補齊表單欄位
- `app/Filament/Resources/Replies/Pages/CreateReply.php` — 新增頁面
- `app/Filament/Resources/Replies/ReplyResource.php` — 註冊 `create` 路由
- `database/migrations/2026_08_15_142945_make_threads_reply_id_nullable_on_replies_table.php` — 讓 `threads_reply_id` 可為 null
- `tests/Feature/ReplyResourceTest.php` — 新增回覆資源測試

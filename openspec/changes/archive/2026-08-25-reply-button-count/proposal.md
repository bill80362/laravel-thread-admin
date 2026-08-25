## Why

目前左側選單「回覆面板」旁邊顯示的 `0/0`（未讀/總數）徽章對管理者幫助有限，真正的回覆操作發生在貼文卡片的「回覆」按鈕上。將計數直接整合到每張貼文卡片的「回覆」按鈕上，能讓管理者在瀏覽貼文列表時立即掌握每則貼文的回覆狀況，無需跳轉到回覆面板。

## What Changes

- **移除側邊欄「回覆面板」的 Navigation Badge**：刪除 `ReplyResource::getNavigationBadge()` 及其 badge 顏色設定，不再顯示 `0/0`。
- **貼文卡片「回覆」按鈕顯示回覆計數**：將按鈕標籤從固定「回覆」改為 `回覆 (未讀數/總數)`，例如「回覆 (3/5)」。
- **保留「有新回覆」badge 或替換**：由於按鈕已顯示未讀數，可評估是否仍需要原有的「有新回覆」文字 badge（本提案保留其存在，不強制移除）。

## Capabilities

### New Capabilities
*（無 — 此變更僅修改既有功能的行為表現，不引入新能力。）*

### Modified Capabilities
- `post-reply-drawer`：「回覆」按鈕的顯示內容從固定文字改為包含未讀/總數的動態計數，需修改 spec 中的按鈕行為要求。
- `reply-read-status`：未讀回覆計數的應用場景擴展至貼文卡片按鈕標籤，不再僅用於「有新回覆」警示 badge。

## Impact

- `app/Filament/Resources/Replies/ReplyResource.php` — 移除 `getNavigationBadge()` 及 `getNavigationBadgeColor()`
- `app/Filament/Resources/Posts/Pages/ListPosts.php` — `viewReplies` action 的 `label()` 改為動態生成

## Context

`CollectThreadsReplies::collectForAccount()` 使用 `Reply::query()->firstOrCreate()` 建立輪詢抓回的回覆，但 `firstOrCreate` 的第二個參數（建立時寫入的欄位）未包含 `user_id`。由於 `ReplyResource::getEloquentQuery()` 以 `user_id = auth()->id()` 過濾，這些 `user_id = null` 的回覆不會出現在後台回覆面板。

現有資料庫中已存在 `user_id = null` 的輪詢回覆（id 10-16），需一併回填。

## Goals / Non-Goals

**Goals:**
- 輪詢抓回的回覆正確記錄 `user_id`（取自帳號所屬使用者）。
- 既有 `user_id = null` 的回覆資料回填正確的 `user_id`。
- 測試涵蓋輪詢建立回覆帶有 `user_id` 的行為。

**Non-Goals:**
- 不改變輪詢機制本身（間隔、端點、欄位）。
- 不處理 Webhook 來源（目前未實作）。

## Decisions

### 建立回覆時補上 `user_id`
在 `firstOrCreate` 的建立欄位中加入 `'user_id' => $account->user_id`。帳號本身已歸屬於使用者，因此直接取自帳號即可，無需依賴 `auth()`（排程 Job 無登入使用者）。

**替代方案考量：**
- 從 `$post->user_id` 取得：貼文也歸屬使用者，但語意上回覆應歸屬於帳號所屬使用者，兩者通常一致；取 `$account->user_id` 更直接且與「回覆歸屬於帳號」一致。

### 2. 既有資料回填
提供一次性資料修正，將 `replies.user_id` 為 `null` 的記錄，依 `threads_account_id` 對應的 `threads_accounts.user_id` 回填。以 migration 實作，確保可重現。

## Risks / Trade-offs

- [既有 `user_id = null` 的回覆若帳號已刪除則無法回填] → 回填 migration 僅處理帳號仍存在的記錄；若帳號已刪除，該回覆已無意義，可保留 `null` 或由後續清理處理。
- [排程 Job 無登入使用者，若誤用 `auth()->id()` 會得到 null] → 明確改為 `$account->user_id`，避免依賴 auth 狀態。

# 回覆發佈對齊設計（align-reply-publishing）

> 日期：2026-08-16
> 對應 OpenSpec change：`align-reply-publishing`

## 目的

將「新增貼文回覆」與「回應回覆」兩種動作統一為「實際發佈到 Threads」，並讓後台介面與 MCP 工具在名詞與行為上保持一致，消除「建立後只寫入本機、沒有發佈」的誤解。

## 背景

- 後台「回覆」按鈕在 `RepliesTable` 內直接呼叫 `ThreadsClient` 兩階段發佈，但沒有重試與狀態追蹤。
- 後台「新增」頁面與 MCP `create-reply` 只透過 `ReplyService::create()` 寫入本機資料庫，**不發佈**。
- 貼文發佈已有一致的兩階段非同步模式（`PublishScheduledPost`），含延遲、重試、token 失效與限流處理。

## 決策

### D1: 維持單一 `Reply` model
維持現有單一 `replies` 表同時承載「收到的留言」與「發出的回覆」，以 `source` 區分來源、以 `status` 區分狀態。不改資料模型結構。

### D2: 擴充 `ReplyStatus`
在現有 `new`／`replied`／`ignored` 之外，新增 `publishing`（發佈中）與 `failed`（發佈失敗）。

| 狀態 | 語義 |
|---|---|
| `new` | 待發佈 |
| `publishing` | 發佈中 |
| `replied` | 已成功發佈 |
| `failed` | 發佈失敗 |
| `ignored` | 忽略 |

### D3: 回覆發佈走非同步 Job（`PublishReply`）
回覆發佈對齊貼文的兩階段非同步模式，並具備重試、token 失效、限流處理。

### D4: `reply_to_id` 來源推導
- 「回應回覆」：`threads_reply_id` 非空 → 回覆該則留言。
- 「新增貼文回覆」：`threads_reply_id` 為空 → 回覆該篇貼文（`post.threads_media_id`）。

### D5: 發佈邏輯收斂到 `ReplyService`
後台 action 與 MCP tool 都呼叫 Service，不再各自呼叫 `ThreadsClient`。

### D6: 移除 `author_username`（手動新增時）
「新增貼文回覆」的表單與 MCP 參數移除「留言者」欄位，`post_id` 改為必填。發佈者即所選帳號本人。

### D7: 新增發佈相關欄位
`replies` 表新增 `error_message` 與 `publish_attempts`，對齊 `posts` 表。

### D8: 發佈延遲共用貼文常數
`PublishReply` 直接引用 `PublishScheduledPost::PUBLISH_DELAY_SECONDS`（30 秒）。

### D9: 回覆列表說明區加入發佈延遲說明
在 `RepliesSyncNotice` 的 Blade view 中加入「回覆發佈約 30 秒後才會顯示在 Threads 上」的說明，數值取自共用常數。

## 架構

```
新增貼文回覆（後台按鈕 / MCP create-reply）
  輸入：threads_account_id + post_id（必填）+ text
  → ReplyService::createPostReply()
      建立 Reply (source=manual, status=new, reply_id=null)
  → dispatch(PublishReply)
  → [PublishReply] createTextContainer(text, reply_to_id=post.threads_media_id)
  → 等 30 秒（共用常數）
  → publishContainer → status=replied / failed（可重試）

回應回覆（後台列表每筆的按鈕）
  輸入：text（該筆記錄已有 threads_reply_id）
  → ReplyService::publish(reply)
  → [PublishReply] createTextContainer(text, reply_to_id=reply.threads_reply_id)
  → 等 30 秒
  → publishContainer → status=replied / failed（可重試）
```

## Service 與 Job

```
ReplyService
├── createPostReply(threads_account_id, post_id, text): Reply
│     建立貼文回覆記錄（source=manual, status=new）
│     驗證：post 必須存在且已發佈（threads_media_id 非空）
│     建立後 dispatch(PublishReply)
├── publish(Reply): void
│     觸發回覆發佈（供「回應回覆」按鈕使用）
│     驗證：threads_reply_id 非空
│     dispatch(PublishReply)
└── resolveReplyToId(Reply): string
      threads_reply_id 非空 → 回覆留言
      threads_reply_id 為空 → 回覆貼文（post.threads_media_id）

PublishReply(replyId, ?creationId)
├── 階段 1（creationId 為 null）
│     createTextContainer(text, replyToId) → status=publishing → 延遲 30 秒重新 dispatch
├── 階段 2（creationId 有值）
│     publishContainer(creationId) → status=replied, replied_at=now()
└── 錯誤處理（與貼文一致）
      token 失效 → 帳號 needs_reauth + 回覆 failed
      限流 → failed
      可重試 → 退避重試（最多 3 次）
      其他 → failed + error_message
```

常數：
- `PUBLISH_DELAY_SECONDS` → 引用 `PublishScheduledPost::PUBLISH_DELAY_SECONDS`
- `MAX_PUBLISH_ATTEMPTS` → 3
- `RETRY_BACKOFF_SECONDS` → 60

## 介面變更

### 後台 `/admin/replies`
- 新增按鈕：「新增」→「新增貼文回覆」
- 列表每筆動作：「回覆」→「回應回覆」
- 說明區加入：「回覆發佈採兩階段機制，建立後約 30 秒才會顯示在 Threads 上」

### 表單（`ReplyForm`）
- 移除「留言者」欄位
- 「所屬貼文」改為必填

### MCP `create-reply`
- 描述：「建立一筆手動回覆記錄」→「建立一筆貼文回覆並發佈至 Threads」
- 移除 `author_username` 參數
- `post_id` 改為必填

## 遷移

1. 新增 migration：`replies` 表加 `error_message`、`publish_attempts`。
2. 擴充 `ReplyStatus` enum（`publishing`、`failed`）。
3. 刪除既有 `source=manual` 且 `threads_reply_id IS NULL` 的歷史記錄。

## 風險與緩解

- [單一 model 語義仍混雜] → 以 `source` + `status` 區分，UI 名詞區隔。
- [非同步發佈的回饋延遲] → 列表顯示狀態 + 說明區提示。
- [Threads 兩階段延遲秒數] → 共用貼文常數，確保同步。

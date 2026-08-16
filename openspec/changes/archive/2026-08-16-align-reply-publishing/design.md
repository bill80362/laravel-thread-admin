## Context

目前回覆（`Reply`）的建立與發佈分散在三處且語義不一致：

- 後台「回覆」按鈕在 `RepliesTable` 內直接呼叫 `ThreadsClient` 兩階段發佈，但沒有重試與狀態追蹤。
- 後台「新增」頁面與 MCP `create-reply` 只透過 `ReplyService::create()` 寫入本機資料庫，**不發佈**。
- 貼文發佈已有一致的兩階段非同步模式（`PublishScheduledPost`），含延遲、重試、token 失效與限流處理。

本設計將回覆的發佈對齊貼文模式，並收斂到 `ReplyService`，同時統一後台與 MCP 的名詞。

## Goals / Non-Goals

**Goals:**
- 讓「新增貼文回覆」與「回應回覆」都實際發佈到 Threads。
- 統一後台與 MCP 的名詞與發佈行為。
- 收斂發佈邏輯到單一 Service，消除分散的 `ThreadsClient` 呼叫。
- 讓回覆發佈具備與貼文一致的狀態追蹤與重試能力。

**Non-Goals:**
- 不拆分 `Reply` model（不區分「收到的留言」與「發出的回覆」資料表）。
- 不變更 Threads API 封裝（`ThreadsClient` 已支援回覆）。
- 不實作帳號綁定或 OAuth 相關功能。

## Decisions

### D1: 維持單一 `Reply` model
維持現有單一 `replies` 表同時承載「收到的留言」與「發出的回覆」，以 `source` 區分來源、以 `status` 區分狀態。

- 替代方案：拆成兩個 model／資料表（語義最乾淨但改動大，需 migration 與大量重構）。
- 理由：使用者訴求是名詞統一與「真的發佈」，而非資料模型重構；單一 model 改動最小。

### D2: 擴充 `ReplyStatus`
在現有 `new`／`replied`／`ignored` 之外，新增 `publishing`（發佈中）與 `failed`（發佈失敗），統一語義為「回覆的發佈狀態」。

- `new`＝待發佈／待處理、`publishing`＝發佈中、`replied`＝已成功發佈、`ignored`＝忽略、`failed`＝發佈失敗。
- 替代方案：新增獨立 enum 供發佈使用（增加複雜度，與現有 `status` 並存易混淆）。
- 理由：`replied` 已存在且含義接近「已回覆」，擴充比新建 enum 更貼近現況。

### D3: 回覆發佈走非同步 Job（`PublishReply`）
回覆發佈對齊貼文的兩階段非同步模式：`createTextContainer` → 延遲 → `publishContainer`，並具備重試、token 失效、限流處理。

- 替代方案：同步立即發佈（阻塞等 30 秒、體驗差且無法重試）；混合模式（後台非同步、MCP 同步，違反一致性訴求）。
- 理由：Threads 容器發佈需要延遲；非同步可重試、可追蹤狀態，與貼文行為一致，且後台與 MCP 行為一致。

### D4: `reply_to_id` 來源推導
發佈時回覆目標（`reply_to_id`）由記錄內容決定：

- 「回應回覆」：`threads_reply_id` 非空 → `reply_to_id = threads_reply_id`（回覆該則留言）。
- 「新增貼文回覆」：`threads_reply_id` 為空 → `reply_to_id = post.threads_media_id`（回覆該篇貼文）。

- 替代方案：新增獨立 `reply_to_id` 欄位儲存發佈目標（多餘，與既有欄位重疊）。
- 理由：不新增欄位即可無歧義推得發佈目標。

### D5: 發佈邏輯收斂到 `ReplyService`
`ReplyService` 新增 `publish(Reply)`（兩階段發佈）與建立貼文回覆的方法；後台 action 與 MCP tool 都呼叫 Service，不再各自呼叫 `ThreadsClient`。

- 理由：遵循專案「業務邏輯收斂到 Service」的既有規則（見 mcp-server spec）。

### D6: 移除 `author_username`（手動新增時）
「新增貼文回覆」的表單與 MCP 參數移除「留言者」欄位，`post_id` 改為必填。

- 理由：發佈者即所選帳號本人，Threads 不接受以他人名義發佈；`author_username` 僅對「收到的留言」（polling）有意義。

### D7: 新增發佈相關欄位
`replies` 表新增 `error_message`（發佈失敗訊息）與 `publish_attempts`（重試計數），對齊 `posts` 表。

- 理由：支援失敗追蹤與重試。

### D8: 發佈延遲共用貼文常數
`PublishReply` 直接引用 `PublishScheduledPost::PUBLISH_DELAY_SECONDS`（30 秒），不另設常數。

- 理由：Threads 兩階段發佈對貼文與回覆機制相同，共用單一常數確保數值同步。

### D9: 回覆列表說明區加入發佈延遲說明
在 `RepliesSyncNotice` 的 Blade view 中加入「回覆發佈約 30 秒後才會顯示在 Threads 上」的說明，數值取自共用常數，確保與實作同步。

- 理由：非同步發佈會造成「建立後未立即出現在 Threads」的誤解，需在介面上說明；數值取自常數符合專案既有規則。

## Risks / Trade-offs

- [單一 model 語義仍混雜] → 以 `source` + `status` 區分，並在 UI 名詞上明確區隔「貼文回覆」與「回應回覆」；日後如需拆分再另行規劃。
- [既有 `source=manual` 且 `threads_reply_id` 為空的歷史記錄無法回應] → 遷移階段決定：保留為「未發佈的貼文回覆」或清理；這些記錄缺少 `threads_reply_id`，無法執行「回應回覆」，僅能視為歷史資料。
- [非同步發佈的使用者回饋延遲] → 列表顯示發佈狀態（發佈中／失敗），失敗時提供錯誤訊息與重試入口。
- [Threads 兩階段延遲的實際所需秒數] → 對齊貼文的 `PUBLISH_DELAY_SECONDS`（30 秒），如有差異以 Threads API 回傳的容器狀態為準。

## Migration Plan

1. 新增 migration：`replies` 表加 `error_message`、`publish_attempts` 欄位。
2. 擴充 `ReplyStatus` enum（`publishing`、`failed`）。
3. 既有 `source=manual` 且 `threads_reply_id` 為空、`status=new` 的記錄：**於遷移時刪除**（舊語義下產生的無效手動記錄，內容已無發佈價值）。
4. 部署無需停機；新欄位有預設值，可安全 migrate。

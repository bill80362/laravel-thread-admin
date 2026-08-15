## Context

`PublishScheduledPost` 目前採用兩階段發佈流程（見 proposal.md - Why）。錯誤處理在 `handle()` 的 catch 區塊：`ThreadsApiException` 分為 token 失效、rate limit、其他錯誤三類，全部直接標記 `failed`，無重試。

環境現況：`QUEUE_CONNECTION=sync`（開發中 Job 同步執行），但 Job 本身是 `ShouldQueue`，可搭配 Laravel Queue 的 `$tries` / `$backoff` / `retryUntil()` 機制。由於兩階段流程以「re-dispatch 自己」的方式實作（第一階段成功後 dispatch 第二階段，帶 `creationId`），不能簡單靠 Laravel 的 `$tries` 自動重試——因為 Job 每次都是獨立 dispatch 的新實例，且失敗狀態需要能回溯到同一個 `creationId`。

## Goals / Non-Goals

**Goals:**
- 為暫時性 API 錯誤提供有限次數的重試，避免貼文卡死在 `failed`。
- 維持兩階段流程與現有狀態機（scheduled → publishing → published/failed）。
- 區分「可重試」與「永久性」錯誤。

**Non-Goals:**
- 不引入新的佇列套件或外部服務（如 Horizon、Redis 延遲佇列）。
- 不改變 token 失效 / rate limit 的現有處理行為。
- 不處理第一階段（createTextContainer）失敗後 creationId 的清理——Threads 未發佈的 container 會自動過期。

## Decisions

### Decision 1: 在 Post 記錄上增加重試計數欄位
- **做法**：`posts` 表新增 `publish_attempts`（integer, default 0）。每次 Job 執行發文前遞增，超過上限則標記 failed。
- **理由**：兩階段各自 dispatch 新 Job，Laravel Queue 的 `$tries` 是「單一 Job 實例」的重試，無法跨兩個 dispatch 累計。自管計數欄位才能精確控制整篇貼文的總重試次數。
- **替代方案**：用 Job 的 payload 傳遞 `attempts` 參數。缺點是每次 dispatch 都要透傳，且無法在資料庫層面查詢/觀察，不如欄位直觀。

### Decision 2: 以「延遲重新 dispatch」實作重試
- **做法**：在 catch 到可重試錯誤時，若 `publish_attempts < MAX`，將 `publish_attempts` 遞增後，`static::dispatch($postId, $creationId)->delay(backoff)` 重新入隊。
- **理由**：`QUEUE_CONNECTION=sync` 下 `delay()` 會被忽略（立即執行），但在 `database` queue 下會正常延遲。此作法在兩種 queue driver 下都能運作，且不依賴 worker 的 `--tries` 設定。
- **替代方案**：使用 Laravel `$backoff` + 拋出例外讓 worker 重試。缺點是 `sync` driver 下會直接無限同步迴圈，且難以跨兩階段累計次數。

### Decision 3: 在 `ThreadsApiException` 新增 `isRetryable()`
- **做法**：判斷 HTTP status ≥ 500、網路層 GuzzleException（無 status）、或 message 包含 `resource does not exist`（本次實測的暫時性錯誤）。
- **理由**：集中在 Exception 內封裝，Job 只需呼叫 `$e->isRetryable()`，與既有 `isTokenInvalid()` / `isRateLimited()` 風格一致。
- **替代方案**：直接在 Job 內寫判斷。缺點是邏輯散落、不易測試與重用。

### Decision 4: 重試上限與退避策略
- **做法**：`MAX_PUBLISH_ATTEMPTS = 3`；退避 `backoff = attempt * 60` 秒（60s、120s）。
- **理由**：Threads 暫時性錯誤通常幾分鐘內恢復，3 次 × 退避已足以涵蓋常見情境，同時避免過度呼叫 API。

## Risks / Trade-offs

- **[重試可能加劇 API 壓力]** → 上限 3 次且退避遞增，總呼叫量受控。
- **[sync driver 下 delay 失效]** → 開發環境立即重試，可接受；正式環境用 database queue 時延遲才會生效。
- **[「resource does not exist」誤判為暫時性]** → 若其實是永久性（帳號設定問題），會在 3 次重試後仍標 failed，最終結果一致，僅多耗幾次呼叫。
- **[資料庫遷移需同步執行]** → 新增 `publish_attempts` 欄位需 migration，不影響既有貼文（default 0）。

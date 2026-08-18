## Context

目前 `threads_apps` 已有 `user_id` 且 `ThreadsAppResource::getEloquentQuery()` 已做 scope。但 `threads_accounts`、`posts`、`replies`、`threads_oauth_states` 皆無 `user_id`，導致跨使用者資料洩漏。詳見 proposal.md。

現有關聯鏈：`User → ThreadsApp → ThreadsAccount → Post/Reply`。本次變更在每張表直接加入 `user_id`，寫入時取自 `auth()->id()`（方案 B），查詢時以 `user_id` 直接 scope，不需 JOIN 多層。

## Goals / Non-Goals

**Goals:**
- 4 張業務表（threads_accounts、posts、replies、threads_oauth_states）加入 `user_id` 欄位與 index
- Filament 後台：ThreadsAccount、Post、Reply 三個 Resource 僅顯示當前 user 的資料
- MCP 工具：所有查詢/寫入操作以 OAuth token 所屬 user scope
- 現有資料全數歸屬 `user_id = 2`

**Non-Goals:**
- 不改變 OAuth 綁定流程的整體架構（僅在 OAuthState 加 user_id 做安全校驗）
- 不修改 Job 層（PublishScheduledPost、CollectThreadsReplies、PublishReply 操作已存在的 record，不涉及跨 user 查詢）
- 不修改 ThreadsAppResource（已有 `getEloquentQuery()` scope）

## Decisions

### Decision 1: `user_id` 寫入方式採用方案 B（直接 `auth()->id()`）

**選擇**：寫入時直接從 `auth()->id()` 取值，不從關聯鏈推導。

**理由**：
- 簡單直接，不需要在 create 時多餘地查詢上層關聯
- 與 ThreadsApp 現有模式一致（ThreadsApp 建立時也是直接 `auth()->id()`）
- MCP HTTP 模式走 `auth:api`，`auth()->id()` 可正確取得 OAuth token 所屬 user

**替代方案**：從關聯鏈推導（account.user_id 來自 app.user_id，post.user_id 來自 account.user_id）。此方案較防禦性但增加複雜度，且與現有 ThreadsApp 模式不一致。

### Decision 2: 隔離策略採用顯式 Scope（方案 B）

**選擇**：在 Filament `getEloquentQuery()` 和 Service 方法中顯式加上 `user_id` scope，不使用 Global Scope。

**理由**：
- 行為透明，易於理解與除錯
- 與現有 `ThreadsAppResource::getEloquentQuery()` 模式一致
- 不影響 Job/Command 等背景程序
- 測試簡單，不需特殊處理

**替代方案**：Global Scope 會隱式過濾所有查詢，但 Job 中 `auth()->id()` 為 null 需特別處理，且除錯困難。

### Decision 3: Filament Form 的 Select 需額外 scope

**選擇**：PostForm 和 ReplyForm 中的 relationship select 需加上 `modifyQueryUsing`。

**理由**：
- `getEloquentQuery()` 已在 Resource 層 scope 了主 model，但 Form 中的 relationship select 是獨立查詢
- 需要在 PostForm/ReplyForm 的 `threads_account_id` Select 加上 `->modifyQueryUsing(fn ($query) => $query->where('user_id', auth()->id()))`
- ReplyForm 的 `post_id` Select 也需 scope：`->modifyQueryUsing(fn ($query) => $query->where('user_id', auth()->id()))`

### Decision 4: 資料庫不加 FK，僅 index

**選擇**：`user_id` 使用 `unsignedBigInteger` + index，不加 foreign key constraint。

**理由**：使用者要求簡化，不需資料庫層級的外鍵約束。

### Decision 5: Service 層以參數傳遞 userId

**選擇**：PostService 和 ReplyService 的方法加入 `?int $userId = null` 參數，預設 `null` 時自動取 `auth()->id()`。

**理由**：
- 保持 Service 的可測試性（測試時可傳入特定 userId）
- 向後相容（現有呼叫者不需修改）
- MCP Tools 和 Filament 都透過 Service，統一在 Service 層做 scope

### Decision 6: MCP Tools 的 user scope 策略

**選擇**：ListAccountsTool 直接在 Tool 內以 `auth()->id()` scope；ListPostsTool/GetPostTool/CreatePostTool 透過 PostService 傳入 userId；ListRepliesTool/CreateReplyTool 透過 ReplyService 傳入 userId。

**理由**：
- ListAccountsTool 直接查 ThreadsAccount model，不經過 Service
- Post/Reply 相關 Tool 已使用 Service，只需在呼叫 Service 時傳入 userId
- CreatePostTool/CreateReplyTool 需額外驗證 `threads_account_id` 歸屬於當前 user

## Risks / Trade-offs

- **Migration 順序依賴**：4 個 migration 需依序執行（先加欄位，再 seed 資料），但 Laravel migration 依檔名時間戳排序，需確保 seeder migration 的時間戳在最後
- **現有資料遺漏**：若未來有新的測試資料在 migration 之間寫入，可能遺漏 `user_id`。→ 在 migration 中使用 `UPDATE ... SET user_id = 2 WHERE user_id IS NULL` 而非依賴 seeder
- **MCP local 模式無 user**：`Mcp::local` 走 Artisan command，無 HTTP 認證，`auth()->id()` 為 `null`。→ 這是預期行為，local 模式僅供開發測試使用

## Migration Plan

1. 依序執行 4 個 migration（加欄位 + index）
2. 執行 seeder migration：`UPDATE` 所有現有資料 `user_id = 2`
3. 部署後驗證：分別以 bill (id=1) 和 donnie (id=2) 登入，確認各自只看得到自己的資料

**Rollback**：每個 migration 的 `down()` 移除 `user_id` 欄位即可還原。

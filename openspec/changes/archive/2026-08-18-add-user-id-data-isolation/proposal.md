## Why

目前系統僅 `threads_apps` 有 `user_id` 隔離，但 `threads_accounts`、`posts`、`replies`、`threads_oauth_states` 皆無使用者歸屬。導致不同使用者登入後台或透過 MCP 查詢時，會看到其他使用者的帳號、貼文與回覆資料，造成資料外洩風險。

## What Changes

- **threads_accounts** 新增 `user_id` 欄位，寫入時取自 `auth()->id()`
- **posts** 新增 `user_id` 欄位，寫入時取自 `auth()->id()`
- **replies** 新增 `user_id` 欄位，寫入時取自 `auth()->id()`
- **threads_oauth_states** 新增 `user_id` 欄位，寫入時取自 `auth()->id()`
- Filament Resources（ThreadsAccount、Post、Reply）加入 `getEloquentQuery()` 以 `user_id`  scope
- MCP 所有工具（ListAccounts、ListPosts、GetPost、CreatePost、ListReplies、CreateReply）加入 user scope，僅回傳/操作 OAuth token 所屬使用者的資料
- PostService、ReplyService 加入 user scope 參數
- 現有資料全數歸屬 `user_id = 2`（donnie）

## Capabilities

### New Capabilities
- `data-isolation`: 所有業務資料（帳號、貼文、回覆、OAuth 狀態）以 `user_id` 進行使用者層級隔離，確保每位使用者僅能存取自己的資料

### Modified Capabilities
- `threads-app-management`: ThreadsAccount 除了透過 `threads_app_id` 間接歸屬 user 外，新增直接的 `user_id` 欄位，查詢時以 `user_id` 直接 scope
- `mcp-server`: 所有 MCP 工具的查詢與寫入操作皆以 OAuth token 所屬 user 進行 scope，不再回傳全域資料

## Impact

- **資料庫**：4 個 migration（加欄位 + index）+ 1 個 seeder（現有資料歸屬）
- **Models**：ThreadsAccount、Post、Reply、OAuthState 新增 `user_id` fillable 與 `user()` relation
- **Filament**：ThreadsAccountResource、PostResource、ReplyResource 新增 `getEloquentQuery()` scope
- **MCP Tools**：6 個工具全部加入 user scope
- **Services**：PostService、ReplyService 方法簽章加入 `?int $userId` 參數
- **Jobs**：PublishScheduledPost、CollectThreadsReplies、PublishReply 不需修改（操作已存在的 record，不涉及跨 user 查詢）

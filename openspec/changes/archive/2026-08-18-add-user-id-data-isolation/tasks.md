## 1. 資料庫 Migration

- [x] 1.1 建立 migration：`threads_accounts` 新增 `user_id` 欄位（`unsignedBigInteger`，nullable，加 index）
- [x] 1.2 建立 migration：`posts` 新增 `user_id` 欄位（`unsignedBigInteger`，nullable，加 index）
- [x] 1.3 建立 migration：`replies` 新增 `user_id` 欄位（`unsignedBigInteger`，nullable，加 index）
- [x] 1.4 建立 migration：`threads_oauth_states` 新增 `user_id` 欄位（`unsignedBigInteger`，nullable，加 index）
- [x] 1.5 建立 migration：將現有資料 `user_id` 設為 `2`（`UPDATE threads_accounts SET user_id = 2 WHERE user_id IS NULL`，依此類推四張表）

## 2. Model 層

- [x] 2.1 `ThreadsAccount`：`$fillable` 加入 `user_id`，新增 `user(): BelongsTo` relation
- [x] 2.2 `Post`：`$fillable` 加入 `user_id`，新增 `user(): BelongsTo` relation
- [x] 2.3 `Reply`：`$fillable` 加入 `user_id`，新增 `user(): BelongsTo` relation
- [x] 2.4 `OAuthState`：`$fillable` 加入 `user_id`，新增 `user(): BelongsTo` relation

## 3. Filament 後台

- [x] 3.1 `ThreadsAccountResource`：新增 `getEloquentQuery()` 以 `user_id` scope
- [x] 3.2 `PostResource`：新增 `getEloquentQuery()` 以 `user_id` scope
- [x] 3.3 `ReplyResource`：新增 `getEloquentQuery()` 以 `user_id` scope
- [x] 3.4 `PostForm`：`threads_account_id` Select 加上 `relationship()` 第三參數 closure scope 當前 user 的帳號
- [x] 3.5 `ReplyForm`：`threads_account_id` Select 加上 `relationship()` 第三參數 closure scope 當前 user 的帳號；`post_id` Select 加上 scope 當前 user 的貼文
- [x] 3.6 `CreatePost`：`mutateFormDataBeforeCreate()` 寫入 `user_id = auth()->id()`

## 4. Service 層

- [x] 4.1 `PostService::create()`：寫入 `user_id = auth()->id()`，並驗證 `threads_account_id` 歸屬
- [x] 4.2 `PostService::list()`：加入 `?int $userId` 參數，預設 `auth()->id()`，以 `user_id` scope
- [x] 4.3 `PostService::find()`：加入 `?int $userId` 參數，預設 `auth()->id()`，以 `user_id` scope
- [x] 4.4 `ReplyService::createPostReply()`：寫入 `user_id = auth()->id()`，並驗證 `threads_account_id` 與 `post_id` 歸屬
- [x] 4.5 `ReplyService::list()`：加入 `?int $userId` 參數，預設 `auth()->id()`，以 `user_id` scope

## 5. MCP Tools

- [x] 5.1 `ListAccountsTool`：查詢加上 `->where('user_id', auth()->id())`
- [x] 5.2 `ListPostsTool`：透過 `PostService::list()` 預設 `auth()->id()` scope（無需改動）
- [x] 5.3 `GetPostTool`：透過 `PostService::find()` 預設 `auth()->id()` scope（無需改動）
- [x] 5.4 `CreatePostTool`：驗證規則改用 `Rule::exists(...)->where('user_id', auth()->id())`
- [x] 5.5 `ListRepliesTool`：透過 `ReplyService::list()` 預設 `auth()->id()` scope（無需改動）
- [x] 5.6 `CreateReplyTool`：驗證規則改用 `Rule::exists(...)->where('user_id', auth()->id())`

## 6. OAuth 流程

- [x] 6.1 `OAuthState::createForApp()`：建立時寫入 `user_id = auth()->id()`
- [x] 6.2 `OAuthState::resolve()`：查詢時驗證 `user_id = auth()->id()`
- [x] 6.3 `ThreadsOAuthController::callback()`：綁定/更新 ThreadsAccount 時寫入 `user_id = auth()->id()`

## 7. 驗證

- [x] 7.1 執行 `php artisan migrate` 確認 migration 成功
- [x] 7.2 完整測試套件 83/83 通過
- [x] 7.3 執行 `vendor/bin/pint --dirty --format agent` 修正程式碼風格

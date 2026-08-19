## 1. 環境變數與設定

- [x] 1.1 `.env.example` 新增 `THREADS_CLIENT_ID` 與 `THREADS_CLIENT_SECRET` 範本
- [x] 1.2 `config/services.php` 的 `threads` 區塊新增 `client_id` 與 `client_secret`（從 env 讀取）

## 2. 移除 ThreadsApp

- [x] 2.1 建立 migration：移除 `threads_accounts.threads_app_id` 外鍵與欄位、移除 `threads_oauth_states.threads_app_id` 外鍵與欄位、刪除 `threads_apps` 表格
- [x] 2.2 刪除 `app/Models/ThreadsApp.php`
- [x] 2.3 `app/Models/ThreadsAccount.php` 移除 `threadsApp()` 關聯與 `threads_app_id` fillable
- [x] 2.4 `app/Models/User.php` 移除 `threadsApps()` 關聯
- [x] 2.5 `app/Models/OAuthState.php` 移除 `threadsApp()` 關聯、`threads_app_id` fillable、`createForApp()` 改為 `createForUser()`；`resolve()` 移除 `where('user_id', auth()->id())` 安全檢查，純靠 token hash 驗證
- [x] 2.6 刪除 `database/factories/ThreadsAppFactory.php`
- [x] 2.7 `database/factories/ThreadsAccountFactory.php` 移除 `threads_app_id`
- [x] 2.8 刪除 `app/Filament/Resources/ThreadsApps/` 整個目錄
- [x] 2.9 `app/Filament/Resources/ThreadsAccounts/Tables/ThreadsAccountsTable.php` 移除 `threadsApp.name` 欄位與 `threads_app_id` 篩選器、調整重新授權 URL

## 3. 重構 ThreadsClient 與 OAuth 流程

- [x] 3.1 `app/Services/ThreadsClient.php`：`buildAuthorizationUrl`、`exchangeCodeForShortToken`、`exchangeShortForLongToken` 不再接收 `ThreadsApp $app`，改從 `config('services.threads')` 讀取憑證
- [x] 3.2 `app/Http/Controllers/ThreadsOAuthController.php`：`redirect()` 不再接收 `ThreadsApp $app`，改用 config 憑證；`callback()` 使用 `$resolved['user_id']` 取代 `auth()->id()`；`updateOrCreate` 查詢條件加入 `user_id`
- [x] 3.3 `routes/web.php`：OAuth redirect 路由從 `{app}/redirect` 改為 `redirect`
- [x] 3.4 `app/Models/OAuthState.php`：`resolve()` 回傳型別改為 `array{user_id: int, account: ?ThreadsAccount}`，移除 `app` 鍵

## 4. 新增刪除貼文功能

- [x] 4.1 `app/Enums/PostStatus.php` 新增 `Deleting`（刪除中）與 `DeleteFailed`（刪除失敗）狀態，含 label 與 color
- [x] 4.2 `app/Services/ThreadsClient.php` 新增 `deleteMedia(ThreadsAccount $account, string $mediaId): bool` 方法
- [x] 4.3 建立 `app/Jobs/DeletePost.php` Job：呼叫 `ThreadsClient::deleteMedia()`，成功刪除記錄（cascade 刪除關聯的 Reply），失敗設為 `DeleteFailed` 並記錄錯誤；不自動重試（無 `$tries`、無 `backoff`）；token 失效時額外將帳號設為 `NeedsReauth`
- [x] 4.4 `app/Services/PostService.php` 新增 `delete(int $postId): void` 方法：驗證所有權、檢查狀態（僅 `Published` 或 `DeleteFailed`）、設為 `Deleting`、dispatch `DeletePost` job
- [x] 4.5 `app/Filament/Resources/Posts/Tables/PostsTable.php`：調整 `DeleteAction` 邏輯 — `Draft`/`Scheduled`/`Publishing`/`Failed` 直接刪除，`Published`/`DeleteFailed` 走 `PostService::delete()` 流程，`Deleting` 隱藏刪除按鈕

## 5. MCP 調整

- [x] 5.1 `routes/ai.php` 移除 `Mcp::local('threads', ThreadsMcpServer::class)` 該行
- [x] 5.2 `app/Mcp/Servers/ThreadsMcpServer.php` 更新 `#[Instructions]` 說明

## 6. 文件更新

- [x] 6.1 `README.md`：移除「多 App 管理」章節，改為「環境變數設定」；更新 OAuth 流程說明
- [x] 6.2 `resources/views/filament/pages/usage-guide/chapter2.blade.php`：改為「環境變數設定 + 綁定帳號」
- [x] 6.3 `resources/views/filament/pages/usage-guide/chapter3.blade.php`：補上刪除貼文狀態說明
- [x] 6.4 `resources/views/filament/pages/usage-guide/chapter5.blade.php`：移除「本地模式」相關內容

## 7. 測試更新

- [x] 7.1 更新 `tests/Feature/ThreadsAppResourceTest.php`（若適用則刪除或改為其他測試）
- [x] 7.2 更新 `tests/Unit/OAuthStateTest.php`：反映 `resolve()` 回傳型別變更
- [x] 7.3 更新 `tests/Unit/ThreadsClientTest.php`：反映方法簽章變更
- [x] 7.4 新增 `tests/Feature/DeletePostTest.php`：測試刪除貼文流程（成功、失敗、重試、狀態限制）
- [x] 7.5 執行 `vendor/bin/pint --dirty --format agent` 修正程式碼風格
- [x] 7.6 執行相關測試確認全部通過

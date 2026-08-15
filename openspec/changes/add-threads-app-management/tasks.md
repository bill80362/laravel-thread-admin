## 1. 資料庫 Migration

- [ ] 1.1 建立 `threads_apps` 表：`id`、`user_id`（nullable FK→users，nullOnDelete）、`name`、`client_id`、`client_secret`（text）、timestamps
- [ ] 1.2 建立 `threads_oauth_states` 表：`id`、`token_hash`（unique）、`threads_app_id`（FK→threads_apps，cascadeOnDelete）、`threads_account_id`（nullable FK→threads_accounts，set null）、`expires_at`、timestamps
- [ ] 1.3 `threads_accounts` 表新增 `threads_app_id`（nullable FK→threads_apps，set null）
- [ ] 1.4 資料落地 migration：讀取 `env('THREADS_CLIENT_ID')`/`env('THREADS_CLIENT_SECRET')`，若兩者皆非空則建立一筆 `threads_apps`（`user_id` 取第一個存在的 User，無則 null），並將既有 `threads_accounts` 全部關聯到該 App
- [ ] 1.5 執行 `php artisan migrate` 確認遷移成功

## 2. Model 與關聯

- [ ] 2.1 建立 `ThreadsApp` Model：`fillable` 含 `user_id`、`name`、`client_id`、`client_secret`；`client_secret` 用 `encrypted` cast；`belongsTo(User)`、`hasMany(ThreadsAccount)`
- [ ] 2.2 建立 `ThreadsAppFactory`
- [ ] 2.3 `ThreadsAccount` Model 新增 `threads_app_id` fillable 與 `belongsTo(ThreadsApp)` 關聯
- [ ] 2.4 `User` Model 新增 `hasMany(ThreadsApp)` 關聯

## 3. ThreadsClient OAuth 方法重構

- [ ] 3.1 `buildAuthorizationUrl` 改為接收 `ThreadsApp $app`，從 `$app->client_id` 讀取 client_id，redirect_uri 仍讀 `config('services.threads.redirect_uri')`
- [ ] 3.2 `exchangeCodeForShortToken` 改為接收 `ThreadsApp $app`，從 `$app` 讀取 `client_id`/`client_secret`
- [ ] 3.3 `exchangeShortForLongToken` 改為接收 `ThreadsApp $app`，從 `$app` 讀取 `client_secret`
- [ ] 3.4 `refreshLongLivedToken` 維持不變（不需 client_secret）

## 4. OAuth State 管理

- [ ] 4.1 建立 `OAuthState` Model（對應 `threads_oauth_states` 表）：`fillable` 含 `token_hash`、`threads_app_id`、`threads_account_id`、`expires_at`
- [ ] 4.2 實作 `OAuthState::createForApp(ThreadsApp $app, ?ThreadsAccount $account)`：產生不透明 token → 存 hash → 回傳原始 token
- [ ] 4.3 實作 `OAuthState::resolve(string $token)`：hash 查表 → 驗證未過期 → 刪除記錄 → 回傳 `['app' => ThreadsApp, 'account' => ?ThreadsAccount]`，失敗則回傳 null

## 5. OAuth Controller 重構

- [ ] 5.1 路由改為 `GET /threads/oauth/{app}/redirect`（route model binding `ThreadsApp`），命名 `threads.oauth.redirect`
- [ ] 5.2 `redirect` 方法：驗證 `$app->user_id === auth()->id()`（或 null），建立 OAuthState，導向授權頁
- [ ] 5.3 `callback` 方法：解析 state → 取得 App 與可選的目標帳號 → 用 App 憑證換 token → `updateOrCreate` ThreadsAccount（帶 `threads_app_id`）→ 若為重新授權則更新既有帳號 token 與狀態
- [ ] 5.4 移除舊的 session-based state 邏輯

## 6. Config 與 .env 調整

- [ ] 6.1 `config/services.php`：`threads.redirect_uri` 改為 `rtrim((string) config('app.url'), '/').'/threads/oauth/callback'`；移除 `threads.client_id` 與 `threads.client_secret`
- [ ] 6.2 `.env`：移除 `THREADS_CLIENT_ID`、`THREADS_CLIENT_SECRET`；`THREADS_REDIRECT_URI` 改為 `THREADS_REDIRECT_URI="${APP_URL}/threads/oauth/callback"`
- [ ] 6.3 `.env.example`：同步更新

## 7. Filament - ThreadsAppResource（新增）

- [ ] 7.1 建立 `ThreadsAppResource`（`php artisan make:filament-resource ThreadsApp --no-interaction`）
- [ ] 7.2 列表頁：顯示 `name`、`client_id`、建立時間；`->modifyQueryUsing()` 過濾 `user_id === auth()->id()`
- [ ] 7.3 表單：`name`（必填）、`client_id`（必填）、`client_secret`（必填、password 遮罩）；`mutateFormDataBeforeCreate` 帶入 `user_id = auth()->id()`
- [ ] 7.4 Toolbar action「綁定帳號」：導向 `route('threads.oauth.redirect', ['app' => $app])`
- [ ] 7.5 Row action「綁定帳號」：同上

## 8. Filament - ThreadsAccountResource 調整

- [ ] 8.1 列表新增 `SelectFilter`：以 `threads_app_id` 篩選，顯示 App 名稱
- [ ] 8.2 列表新增 `TextColumn::make('threadsApp.name')->label('所屬 App')`
- [ ] 8.3 新增 row action「重新授權」：導向 `route('threads.oauth.redirect', ['app' => $account->threads_app_id])`，並在 state 帶入該帳號 id
- [ ] 8.4 移除 toolbar action「綁定帳號」（改由 App 資源發起）

## 9. 測試

- [ ] 9.1 更新 `ThreadsClientTest`：OAuth 方法改為傳入 `ThreadsApp`
- [ ] 9.2 新增 `ThreadsAppResourceTest`：CRUD 與權限隔離測試
- [ ] 9.3 更新 `ThreadsAccountResource` 相關測試：確認 App 篩選與重新授權入口
- [ ] 9.4 執行 `php artisan test --compact` 確認全部通過

## 10. 格式化與最終檢查

- [ ] 10.1 執行 `vendor/bin/pint --format agent`
- [ ] 10.2 確認 `.env` 與 `.env.example` 已正確更新
- [ ] 10.3 手動驗證：建立 App → 綁定帳號 → 重新授權 → 多 App 各自獨立

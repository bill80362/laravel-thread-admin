## 1. 專案環境補齊

- [x] 1.1 將 `guzzlehttp/guzzle` 提升為正式依賴（`composer require guzzlehttp/guzzle`）
- [x] 1.2 執行 `php artisan filament:install --panels --no-interaction` 建立 Admin Panel
- [x] 1.3 確認 `app/Providers/Filament/AdminPanelProvider.php` 已生成，並設定登入保護
- [x] 1.4 執行 `php artisan queue:table` 與 `php artisan migrate`，確認 database Queue 可用

## 2. 設定檔案

- [x] 2.1 在 `config/services.php` 新增 `threads` 區塊（client_id / client_secret / redirect_uri）
- [x] 2.2 在 `.env.example` 新增 `THREADS_CLIENT_ID`、`THREADS_CLIENT_SECRET`、`THREADS_REDIRECT_URI`
- [x] 2.3 在 `bootstrap/app.php` 以 `withSchedule()` 註冊排程（發文檢查、回覆拉取、token 續命）
- [x] 2.4 在 `routes/console.php` 定義對應的排程命令

## 3. 資料模型

- [x] 3.1 建立 `threads_accounts` migration（含 encrypted access_token、token_expires_at、status、last_synced_at）
- [x] 3.2 建立 `posts` migration（text ≤500、scheduled_at、status、threads_media_id、error_message）
- [x] 3.3 建立 `replies` migration（threads_reply_id unique、source、status、replied_at）
- [x] 3.4 建立對應 Eloquent Model（`ThreadsAccount`、`Post`、`Reply`）與關聯、enum cast
- [x] 3.5 建立對應 Factory（供測試用）

## 4. Threads API 服務層

- [x] 4.1 建立 `App\Services\ThreadsClient`，封裝授權 URL 產生、code→短 token→長 token、續命、發文（container/publish）、讀回覆、回覆、讀取個人資料等方法
- [x] 4.2 建立 OAuth 回調路由與 Controller，處理授權碼交換與帳號儲存
- [x] 4.3 建立 `RefreshThreadsTokens` Job（每日續命，失敗標記 needs_reauth）

## 5. 排程發文

- [x] 5.1 建立 `PublishScheduledPost` Job（兩階段：建 container → delay 30s → 發佈，狀態/錯誤回填）
- [x] 5.2 建立 `PostResource`（Filament），支援建立/編輯/刪除未發貼文、狀態欄位與錯誤訊息顯示
- [x] 5.3 設定 Scheduler 每分鐘派發到期貼文至 Queue

## 6. 回覆收集與管理

- [x] 6.1 建立 `CollectThreadsReplies` Job（Polling 拉取，依 threads_reply_id 去重，更新 last_synced_at）
- [x] 6.2 建立 `ReplyResource`（Filament 唯讀列表 + 篩選）
- [x] 6.3 實作「快速回覆」與「忽略」Action（呼叫 ThreadsClient，更新狀態）
- [x] 6.4 設定 Scheduler 定期觸發回覆收集（預設 5 分鐘）

## 7. 帳號管理介面

- [x] 7.1 建立 `ThreadsAccountResource`（列表顯示 token 狀態、綁定/解除綁定 Action、需重新授權警示）
- [x] 7.2 建立 Dashboard Widget（帳號狀態概覽、待處理回覆數）

## 8. 測試

- [x] 8.1 撰寫 `ThreadsClient` 單元測試（mock Guzzle，涵蓋成功/失敗/續命）
- [x] 8.2 撰寫排程發文 Feature 測試（到期發佈、token 失效、rate limit）
- [x] 8.3 撰寫回覆收集 Feature 測試（新回覆入庫、去重、token 失效跳過）
- [x] 8.4 撰寫 Filament Resource 測試（建立貼文驗證、回覆面板動作）

## 9. 文件

- [x] 9.1 重寫 `README.md`：專案簡介、Roadmap、技術棧、安裝步驟、Meta App 前置設定、OAuth 綁定流程、排程/Queue 設定、操作說明

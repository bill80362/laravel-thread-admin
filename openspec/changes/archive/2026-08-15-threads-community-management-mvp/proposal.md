## Why

營運人員目前無法在單一介面管理多個 Threads 帳號，發文、收集中回覆、快速回應都需要在 Threads 官方 App 之間反覆切換，效率低落且缺乏排程能力。這個專案初期目標是先建立一個可用的 MVP 營運平台，把「多帳號 → 集中管理 → 定時排程發文 → 集中收集回覆 → 快速回覆」這條核心工作流串起來，為中期 AI 產文、後期 AI 自動化奠定基礎。

## What Changes

- 建立 Filament 管理後台（Panel），作為唯一操作介面，單一管理員登入後管理多個 Threads 帳號。
- 新增 Threads OAuth 綁定流程：授權視窗 → 授權碼 → 短效 token → 長效 token（60 天），並支援到期前自動續命與「需重新授權」狀態標記。
- 新增 `ThreadsClient` 服務層，以 Guzzle 封裝 Threads Graph API 呼叫（發文、讀回覆、回覆、讀取個人資料）。
- 新增帳號管理：綁定/解除綁定 Threads 帳號、顯示 token 有效狀態與最後同步時間。
- 新增排程發文：建立/編輯/刪除純文字貼文（≤500 字），設定排程時間，由 Laravel Scheduler + Queue 定時發佈。
- 新增回覆收集：以 Polling 排程定期拉取各帳號最新回覆並入庫，顯示於集中回覆面板。
- 新增快速回覆：在回覆面板直接回覆留言，並記錄回覆狀態。
- 補齊專案設定檔（`config/services.php` 的 threads 區塊、`.env` 範本、排程/Queue 設定），並以全新 README.md 取代 Laravel 預設說明。

## Capabilities

### New Capabilities

- `threads-account-management`: Threads 帳號的 OAuth 綁定、token 生命週期管理（取得、儲存、續命、失效標記）。
- `post-scheduling`: 純文字貼文的建立、排程、發佈與狀態追蹤。
- `reply-collection`: 以 Polling 定期拉取各帳號回覆並集中收錄。
- `reply-management`: 在集中面板查看與快速回覆留言。

### Modified Capabilities

<!-- 專案尚無既有規格，無需修改。 -->

## Impact

- **新增依賴**：將 `guzzlehttp/guzzle` 提升為正式依賴（目前為 Filament 的間接依賴）。
- **新增服務**：`App\Services\ThreadsClient`（Guzzle 封裝）、排程發文 Job、回覆收集 Job、token 續命 Job。
- **新增資料表**：`threads_accounts`、`posts`、`replies`（含 migration）。
- **設定檔案**：`config/services.php`（threads 區塊）、`.env.example`（THREADS_* 變數）、`bootstrap/app.php`（排程註冊）、`routes/console.php`（排程命令）。
- **後台**：Filament Panel 及對應 Resource/Widget/頁面。
- **文件**：重寫 `README.md`。

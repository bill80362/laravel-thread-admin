## Context

現有後台為 Filament 5 單一 Panel（Admin），導覽以扁平方式排列四個 Resource（Threads App、Threads 帳號、排程發文、回覆面板），無 navigation groups，也無任何自訂 Page（`app/Filament/Pages/` 目錄不存在）。動機與範圍見 proposal.md。

MCP 服務已在 `routes/ai.php` 註冊（`Mcp::local` + `Mcp::web`），HTTP 模式以 Passport OAuth（`auth:api` guard）保護，token 存於 `oauth_access_tokens` 表，Client 存於 `oauth_clients` 表。目前沒有任何管理這些 Passport token 的後台介面。

關鍵既有事實（供使用說明內容引用，設計不重複敘述動機）：
- 排程每分鐘觸發 `threads:schedule`，發文兩階段（建 container → 等 30 秒 → 發佈），失敗最多重試 3 次。
- 回覆收集每帳號 5 分鐘一次（`last_synced_at` 判斷），只收集本系統已發佈（有 `threads_media_id`）貼文的回覆。
- `redirect_uri` = `APP_URL` + `/threads/oauth/callback`；`client_id` / `client_secret` 存於 `threads_apps` 表。
- Passport `Token` model：`$keyType='string'`、`$incrementing=false`、`scopes`/`revoked`/`expires_at` 皆有 cast；`Client` model 的 `secret` 有 `$hidden`。

## Goals / Non-Goals

**Goals:**
- 以 Filament 自訂 Page 實作「使用說明」，內容以非程式人員可讀的步驟化說明呈現。
- 以完整 Filament Resource 實作「MCP 控管」，唯讀列出當前使用者的 Passport token 並支援註銷。
- 不新增資料表、不新增 Composer 依賴。

**Non-Goals:**
- 不將使用說明內容做成資料庫驅動（不提供後台編輯說明的功能）。
- 不提供 OAuth Client 的建立／刪除介面（只管理 token）。
- 不引入 navigation groups（維持扁平導覽）。

## Decisions

### 1. 「使用說明」用 Filament 自訂 Page + Blade view
- **理由**：使用說明是長篇、含連結與程式碼區塊的靜態內容，Blade 視圖最靈活，不需要 Filament 表單/Infolist 組件的結構化約束。
- **做法**：`php artisan make:filament-page UsageGuide` 產生 `app/Filament/Pages/UsageGuide.php` 與 `resources/views/filament/pages/usage-guide.blade.php`。Page class 設定 `$navigationIcon`、`$navigationLabel`、`$navigationSort`（50）。
- **替代方案**：使用 Infolist/Section 組件組成。不採用，因步驟說明文字多、需自由排版與超連結，Blade 更直接。

### 2. 「MCP 控管」用完整 Filament Resource + `Laravel\Passport\Token`
- **理由**：token 列表是典型列表頁，Resource 內建 table、filters、actions 與測試工具，擴充性佳。
- **做法**：建立 `McpTokenResource`，直接以 `Laravel\Passport\Token` 作為 model（不建立自訂 model）。`getEloquentQuery()` 覆寫為 `Token::query()->where('user_id', auth()->id())`，確保只看到自己的 token。
- **列表欄位**：`client.name`（TextColumn）、`scopes`（以 badge 顯示）、`created_at`、`expires_at`、`revoked`（IconColumn/Boolean）。
- **動作**：`revoke` Action，呼叫 `$record->revoke()`，完成後顯示通知並刷新列表。
- **替代方案**：用自訂 Page + 內嵌 Table。不採用，因 Resource 提供更完整的過濾、動作與測試支援，且符合「完整 Resource」需求。

### 3. token 註銷用 Passport 內建 `revoke()`
- **理由**：`Laravel\Passport\Token::revoke()` 直接更新 `revoked = true`，無需自寫邏輯。
- **安全**：`getEloquentQuery()` 已限定 `user_id = auth()->id()`，Resource 層即隔離；`revoke` Action 再以 `$record` 操作，天然限制只能動自己的 token。

### 4. 使用說明內容以「非程式語言」為準
- **理由**：目標讀者是營運人員。內容以中文口語、步驟編號、截圖佔位與超連結撰寫，避免出現 class、namespace、migration 等程式術語。
- **內容架構**：前置準備（Meta App 申請）、本系統設定、排程發文、回覆收集、MCP 服務（本地/HTTP、ChatGPT/Claude Desktop 註冊）。

## Risks / Trade-offs

- **[使用說明內容易過時]** → 說明中涉及的排程間隔、發文流程等值取自程式碼常數；在設計中註明內容撰寫時直接引用既有常數，並於 AGENTS.md 提醒後續修改流程時同步更新說明。
- **[Passport Token 表的主鍵為 string]** → Resource 的 `$record` 以 char(80) id 運作；確認 Filament 不需 auto-increment 主鍵即可運作（可正常運作，無需處理）。
- **[scopes 為 array cast，顯示需處理]** → 以 TextColumn 的 `badge()` + `formatStateUsing` 轉成字串陣列顯示，避免直接印出陣列。
- **[Client secret 洩漏風險]** → `Laravel\Passport\Client::$hidden = ['secret']`，且列表只顯示 `client.name`，不顯示 secret；符合 AGENTS.md「不洩漏敏感欄位」原則。

## Migration Plan

- 無資料庫 migration。新增檔案即可上線。
- 部署：`composer dump-autoload`（如需要）→ 清除快取。無 rollback 需求（刪除新增檔案即可）。

## Open Questions

- 無（範圍與實作方式已收斂）。

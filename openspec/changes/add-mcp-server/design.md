## Context

目前應用程式提供 Filament 後台以手動操作貼文與回覆，業務邏輯散佈在 Resource 頁面、Table Action 與 Job 中。專案已安裝 `laravel/mcp`（0.9.3）與 `laravel/passport`（13.7.6），並已執行 `php artisan install:api --passport`。現有模型：`ThreadsAccount`、`Post`、`Reply`，Enum：`PostStatus`、`ReplyStatus`、`ReplySource`、`ThreadsAccountStatus`。動機與範圍詳見 `proposal.md`。

## Goals / Non-Goals

**Goals:**
- 建立單一 MCP Server，同時以 local 與 web 兩種方式註冊。
- 提供六個 MCP Tools，讀取帳號、建立排程貼文、查詢貼文／回覆、建立手動回覆。
- 將貼文／回覆的建立與查詢收斂到共用 `PostService`、`ReplyService`，供 MCP 使用（部分抽取，不動既有 Filament 頁面）。
- HTTP 模式以 Passport OAuth 保護。

**Non-Goals:**
- 不提供帳號綁定／OAuth 授權相關 MCP 工具（綁定仍僅限後台介面）。
- 不重構現有 Filament Resource 頁面使其改用 Service（留待後續漸進收斂）。
- 不實作實際透過 Threads API 發送回覆（回覆發送仍保留在 Filament 的 `reply` Action 與既有 Job 中）。

## Decisions

### 1. 使用 `routes/ai.php` 集中註冊 MCP 伺服器
Laravel MCP 以 `routes/ai.php` 為慣例註冊位置。將 local 與 web 註冊都放在此檔，集中管理。

```php
use App\Mcp\Servers\ThreadsMcpServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::oauthRoutes();

Mcp::local('threads', ThreadsMcpServer::class);

Mcp::web('/mcp/threads', ThreadsMcpServer::class)
    ->middleware('auth:api');
```

- **替代方案**：放在 `AppServiceProvider::boot()`。不採用，因 routes 檔語意更清楚且與官方文件一致。
- **理由**：local 用名稱 `threads` 讓編輯器以 `php artisan mcp:start threads` 啟動；web 用路徑 `/mcp/threads` 供遠端 HTTP 存取。

### 2. HTTP 模式使用 Passport OAuth（`auth:api`）
MCP 規格官方認證機制為 OAuth 2.1，Laravel MCP 文件亦建議使用 Passport。`Mcp::oauthRoutes()` 會註冊 OAuth2 discovery 與 client registration 路由，並使用單一 `mcp:use` scope。

- **替代方案**：Sanctum（`auth:sanctum`）。不採用，因專案已明確選用 Passport，且 OAuth 2.1 相容性最佳。
- **理由**：與已安裝的 `laravel/passport` 及 `install:api --passport` 成果一致。

### 3. 工具設計與命名
六個工具以 kebab-case 命名，`handle(Request $request)` 回傳 `Response`。輸入以 `schema(JsonSchema $schema)` 定義，輸出以 JSON 文字（`Response::json(...)` 或結構化）回傳。

| Tool | 說明 | 關鍵輸入 |
|------|------|---------|
| `list-accounts` | 列出帳號 | `status?` |
| `create-post` | 建立排程貼文 | `threads_account_id`, `text`, `scheduled_at` |
| `list-posts` | 查詢貼文 | `threads_account_id?`, `status?` |
| `get-post` | 查詢單一貼文 | `post_id` |
| `list-replies` | 查詢回覆 | `threads_account_id?`, `post_id?`, `status?` |
| `create-reply` | 建立手動回覆 | `threads_account_id`, `post_id?`, `author_username`, `text` |

- **理由**：與 Filament 既有表單／列表欄位對齊，降低認知落差；工具行為需與介面一致。

### 4. 抽取共用 Service 層（部分抽取）
新增 `app/Services/PostService.php` 與 `app/Services/ReplyService.php`，封裝建立與查詢邏輯，供 MCP Tools 呼叫。此為「部分抽取」：新增 Service，但暫不回頭改 Filament 頁面，避免本次變更範圍過大。

- **PostService**：`create(array $data): Post`、`list(array $filters): Collection`、`find(int $id): ?Post`。
- **ReplyService**：`create(array $data): Reply`（自動設 `source=manual`、`status=new`）、`list(array $filters): Collection`。

`create-post` 工具建立貼文時，若提供 `scheduled_at` 即設 `status = PostStatus::Scheduled`，與 Filament `CreatePost::mutateFormDataBeforeCreate` 一致。

- **替代方案**：MCP 工具直接操作 Model。不採用，因會複製 Filament 的驗證與狀態邏輯，日後易漂移。
- **理由**：回應「介面和 MCP 統一，之後修改也要統一」的需求。

### 5. 不實作帳號綁定工具
MCP 工具清單不包含任何 OAuth 授權／綁定操作，僅讀取已綁定帳號。`list-accounts` 預設回傳所有帳號（或依 `status` 篩選），供 agent 得知可用發文／回覆的帳號。

- **理由**：OAuth 授權流程需要瀏覽器互動與 redirect，不適合無互動的 MCP 工具；範圍明確排除綁定操作。

## Risks / Trade-offs

- **[工具回傳格式未統一]** → 所有工具回傳結構化 JSON，並以 outputSchema 或文件說明欄位，降低 agent 解析歧義。
- **[Passport 尚未完成設定]** → 需確認 `install:api --passport` 產生的 keys、guard 與 `auth:api` middleware 已就緒；必要時 publish `mcp-views` 並在 `AppServiceProvider` 設定 `Passport::authorizationView`。
- **[Service 部分抽取造成短期重複]** → Filament 尚未改用 Service，短期存在平行邏輯；以「後續漸進收斂」為原則，並在 `AGENTS.md` 記錄此約定避免漂移。
- **[MCP 工具安全邊界]** → web 模式以 `auth:api` 保護，但仍需確保 agent 只能操作其被授權的資源；後續可於 tool 內加入 authorization 檢查（`$request->user()`）。

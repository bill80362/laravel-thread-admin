# MCP 伺服器 實作計畫

> **面向 AI 代理的工作者：** 必需子技能：使用 superpowers:subagent-driven-development（推薦）或 superpowers:executing-plans 逐任務實作此計畫。步驟使用複選框（`- [ ]`）語法來追蹤進度。

**目標：** 建立一個可透過本地（Artisan）與 HTTP（Passport OAuth）兩種方式存取的 MCP 伺服器，提供六個工具供 AI agent 讀取帳號、建立排程貼文、查詢貼文／回覆、建立手動回覆。

**架構：** 共用業務邏輯收斂到 `PostService` 與 `ReplyService`；MCP 工具呼叫 Service 完成寫入／查詢；伺服器於 `routes/ai.php` 同時以 `Mcp::local` 與 `Mcp::web` 註冊。

**技術棧：** Laravel 13、PHP 8.4、`laravel/mcp` 0.9.3、`laravel/passport` 13.7.6、PHPUnit。

---

## 現狀說明（已完成的前置工作）

下列檔案已在先前會話建立，本計畫以它們為基礎，但**每個任務仍會驗證正確性**：

- `app/Services/PostService.php`（`create()` / `list()` / `find()`）
- `app/Services/ReplyService.php`（`create()` / `list()`）
- `app/Mcp/Servers/ThreadsMcpServer.php`（註冊六個 tools，但 tools 尚未建立）

Passport 環境已就緒：`config/passport.php`、`routes/api.php`、`api` guard（`config/auth.php`）、`HasApiTokens`（`app/Models/User.php`）皆已就位。

---

### 任務 1：建立六個 MCP 工具

**檔案：**
- 建立：`app/Mcp/Tools/ListAccountsTool.php`
- 建立：`app/Mcp/Tools/CreatePostTool.php`
- 建立：`app/Mcp/Tools/ListPostsTool.php`
- 建立：`app/Mcp/Tools/GetPostTool.php`
- 建立：`app/Mcp/Tools/ListRepliesTool.php`
- 建立：`app/Mcp/Tools/CreateReplyTool.php`

**序列化約定：** 所有工具回傳 `Response::structured([...])`，外層以陣列包裝避免空陣列例外；**絕不直接 `toArray()`**，避免洩漏 `access_token`（encrypted cast）等敏感欄位。datetime 由 Carbon 自動 JSON 序列化為 ISO 字串。

- [ ] **步驟 1.1：建立 `ListAccountsTool`**

```php
<?php

namespace App\Mcp\Tools;

use App\Models\ThreadsAccount;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('列出已綁定的 Threads 帳號，供發文與回覆使用。可依狀態篩選。')]
class ListAccountsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $status = $request->get('status');

        $accounts = ThreadsAccount::query()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderBy('id')
            ->get()
            ->map(fn (ThreadsAccount $account): array => [
                'id' => $account->id,
                'username' => $account->username,
                'name' => $account->name,
                'status' => $account->status->value,
            ]);

        return Response::structured(['accounts' => $accounts->all()]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()
                ->description('依帳號狀態篩選（active / needs_reauth / disabled）。留空回傳全部。'),
        ];
    }
}
```

- [ ] **步驟 1.2：建立 `CreatePostTool`**

```php
<?php

namespace App\Mcp\Tools;

use App\Services\PostService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('建立一筆排程貼文，需指定帳號、內容與排程時間。')]
class CreatePostTool extends Tool
{
    public function handle(Request $request, PostService $posts): Response
    {
        $data = $request->validate([
            'threads_account_id' => ['required', 'integer', 'exists:threads_accounts,id'],
            'text' => ['required', 'string', 'max:500'],
            'scheduled_at' => ['required', 'date'],
        ]);

        $post = $posts->create($data);

        return Response::structured([
            'post' => [
                'id' => $post->id,
                'threads_account_id' => $post->threads_account_id,
                'text' => $post->text,
                'scheduled_at' => $post->scheduled_at,
                'status' => $post->status->value,
            ],
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'threads_account_id' => $schema->integer()
                ->description('目標 Threads 帳號 ID')
                ->required(),
            'text' => $schema->string()
                ->description('貼文內容（最多 500 字元）')
                ->required(),
            'scheduled_at' => $schema->string()
                ->description('排程時間（ISO 8601 或 Y-m-d H:i:s）')
                ->required(),
        ];
    }
}
```

- [ ] **步驟 1.3：建立 `ListPostsTool`**

```php
<?php

namespace App\Mcp\Tools;

use App\Services\PostService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('查詢貼文清單，支援依帳號與狀態篩選。')]
class ListPostsTool extends Tool
{
    public function handle(Request $request, PostService $posts): Response
    {
        $data = $request->validate([
            'threads_account_id' => ['nullable', 'integer', 'exists:threads_accounts,id'],
            'status' => ['nullable', 'string', 'in:draft,scheduled,publishing,published,failed'],
        ]);

        $result = $posts->list($data)->map(fn ($post): array => [
            'id' => $post->id,
            'threads_account_id' => $post->threads_account_id,
            'threads_media_id' => $post->threads_media_id,
            'text' => $post->text,
            'scheduled_at' => $post->scheduled_at,
            'published_at' => $post->published_at,
            'status' => $post->status->value,
            'error_message' => $post->error_message,
        ]);

        return Response::structured(['posts' => $result->all()]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'threads_account_id' => $schema->integer()
                ->description('依帳號 ID 篩選'),
            'status' => $schema->string()
                ->description('依狀態篩選（draft / scheduled / publishing / published / failed）'),
        ];
    }
}
```

- [ ] **步驟 1.4：建立 `GetPostTool`**

```php
<?php

namespace App\Mcp\Tools;

use App\Services\PostService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('依貼文 ID 查詢單一貼文詳細資訊。')]
class GetPostTool extends Tool
{
    public function handle(Request $request, PostService $posts): Response
    {
        $data = $request->validate([
            'post_id' => ['required', 'integer'],
        ]);

        $post = $posts->find($data['post_id']);

        if ($post === null) {
            return Response::error("找不到貼文 ID [{$data['post_id']}]");
        }

        return Response::structured([
            'post' => [
                'id' => $post->id,
                'threads_account_id' => $post->threads_account_id,
                'threads_media_id' => $post->threads_media_id,
                'text' => $post->text,
                'scheduled_at' => $post->scheduled_at,
                'published_at' => $post->published_at,
                'status' => $post->status->value,
                'error_message' => $post->error_message,
            ],
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'post_id' => $schema->integer()
                ->description('貼文 ID')
                ->required(),
        ];
    }
}
```

- [ ] **步驟 1.5：建立 `ListRepliesTool`**

```php
<?php

namespace App\Mcp\Tools;

use App\Services\ReplyService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('查詢回覆清單，支援依帳號、貼文與狀態篩選。')]
class ListRepliesTool extends Tool
{
    public function handle(Request $request, ReplyService $replies): Response
    {
        $data = $request->validate([
            'threads_account_id' => ['nullable', 'integer', 'exists:threads_accounts,id'],
            'post_id' => ['nullable', 'integer', 'exists:posts,id'],
            'status' => ['nullable', 'string', 'in:new,replied,ignored'],
        ]);

        $result = $replies->list($data)->map(fn ($reply): array => [
            'id' => $reply->id,
            'threads_account_id' => $reply->threads_account_id,
            'post_id' => $reply->post_id,
            'author_username' => $reply->author_username,
            'text' => $reply->text,
            'status' => $reply->status->value,
            'replied_at' => $reply->replied_at,
        ]);

        return Response::structured(['replies' => $result->all()]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'threads_account_id' => $schema->integer()
                ->description('依帳號 ID 篩選'),
            'post_id' => $schema->integer()
                ->description('依貼文 ID 篩選'),
            'status' => $schema->string()
                ->description('依狀態篩選（new / replied / ignored）'),
        ];
    }
}
```

- [ ] **步驟 1.6：建立 `CreateReplyTool`**

```php
<?php

namespace App\Mcp\Tools;

use App\Services\ReplyService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('建立一筆手動回覆記錄。來源與狀態會自動設為 manual 與 new。')]
class CreateReplyTool extends Tool
{
    public function handle(Request $request, ReplyService $replies): Response
    {
        $data = $request->validate([
            'threads_account_id' => ['required', 'integer', 'exists:threads_accounts,id'],
            'post_id' => ['nullable', 'integer', 'exists:posts,id'],
            'author_username' => ['required', 'string', 'max:255'],
            'text' => ['required', 'string', 'max:500'],
        ]);

        $reply = $replies->create($data);

        return Response::structured([
            'reply' => [
                'id' => $reply->id,
                'threads_account_id' => $reply->threads_account_id,
                'post_id' => $reply->post_id,
                'author_username' => $reply->author_username,
                'text' => $reply->text,
                'status' => $reply->status->value,
            ],
        ]);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'threads_account_id' => $schema->integer()
                ->description('來源 Threads 帳號 ID')
                ->required(),
            'post_id' => $schema->integer()
                ->description('所屬貼文 ID（可選）'),
            'author_username' => $schema->string()
                ->description('留言者使用者名稱（不含 @）')
                ->required(),
            'text' => $schema->string()
                ->description('留言內容（最多 500 字元）')
                ->required(),
        ];
    }
}
```

- [ ] **步驟 1.7：執行 pint 驗證語法**

運行：`vendor/bin/pint --dirty --format agent`
預期：無語法或格式錯誤。

---

### 任務 2：驗證並收斂 Service 層

**檔案：**
- 修改：`app/Services/PostService.php`（如需要）
- 修改：`app/Services/ReplyService.php`（如需要）

- [ ] **步驟 2.1：確認 `PostService` 已提供 `create()` / `list()` / `find()`**

當前實作已包含三個方法，簽名與本計畫工具用法一致（`create(array): Post`、`list(array): Collection`、`find(int): ?Post`）。若與下方測試預期不符，再行調整。

- [ ] **步驟 2.2：確認 `ReplyService` 已提供 `create()` / `list()`，且 `create()` 自動設 `source=manual`、`status=new`**

當前實作已符合。若與下方測試預期不符，再行調整。

- [ ] **步驟 2.3：執行 pint 驗證**

運行：`vendor/bin/pint --dirty --format agent`
預期：無錯誤。

---

### 任務 3：驗證並修正 MCP Server 註冊

**檔案：**
- 修改：`app/Mcp/Servers/ThreadsMcpServer.php`

- [ ] **步驟 3.1：確認 `ThreadsMcpServer` 正確註冊六個工具**

確認 `$tools` 陣列包含 `ListAccountsTool`、`CreatePostTool`、`ListPostsTool`、`GetPostTool`、`ListRepliesTool`、`CreateReplyTool`，且 `use` 引入正確。若缺漏則補齊。

- [ ] **步驟 3.2：確認 Server 屬性（Name / Version / Instructions）**

確認 `#[Name]`、`#[Version]`、`#[Instructions]` 屬性存在且內容合理。

---

### 任務 4：建立 `routes/ai.php` 並註冊伺服器

**檔案：**
- 建立：`routes/ai.php`

- [ ] **步驟 4.1：建立 `routes/ai.php`**

```php
<?php

use App\Mcp\Servers\ThreadsMcpServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::oauthRoutes();

Mcp::local('threads', ThreadsMcpServer::class);

Mcp::web('/mcp/threads', ThreadsMcpServer::class)
    ->middleware('auth:api');
```

- [ ] **步驟 4.2：驗證路由註冊**

運行：`php artisan route:list --path=mcp`
預期：顯示 `/mcp/threads` 的 POST 路由（及 GET/DELETE 的 405 佔位路由）。

運行：`php artisan route:list --path=oauth`
預期：顯示 `/oauth/register` 及 `.well-known/oauth-*` 路由。

---

### 任務 5：發布 Passport 授權視圖

**檔案：**
- 建立：`resources/views/mcp/authorize.blade.php`（經由 vendor:publish）
- 修改：`app/Providers/AppServiceProvider.php`

- [ ] **步驟 5.1：發布 mcp-views**

運行：`php artisan vendor:publish --tag=mcp-views`
預期：建立 `resources/views/mcp/authorize.blade.php`。

- [ ] **步驟 5.2：設定 `Passport::authorizationView`**

在 `AppServiceProvider::boot()` 中加入：

```php
use Laravel\Passport\Passport;

public function boot(): void
{
    if (config('app.force_https')) {
        URL::forceScheme('https');
    }

    Passport::authorizationView(fn ($parameters) => view('mcp.authorize', $parameters));
}
```

- [ ] **步驟 5.3：執行 pint**

運行：`vendor/bin/pint --dirty --format agent`

---

### 任務 6：撰寫測試

**檔案：**
- 建立：`tests/Feature/PostServiceTest.php`
- 建立：`tests/Feature/ReplyServiceTest.php`
- 建立：`tests/Feature/McpToolsTest.php`

- [ ] **步驟 6.1：建立 `PostServiceTest.php`**

```php
<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\ThreadsAccount;
use App\Services\PostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_sets_scheduled_status(): void
    {
        $account = ThreadsAccount::factory()->create();

        $post = app(PostService::class)->create([
            'threads_account_id' => $account->id,
            'text' => '測試貼文',
            'scheduled_at' => now()->addHour(),
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'threads_account_id' => $account->id,
            'text' => '測試貼文',
            'status' => PostStatus::Scheduled->value,
        ]);
    }

    public function test_list_filters_by_account_and_status(): void
    {
        $account = ThreadsAccount::factory()->create();
        $other = ThreadsAccount::factory()->create();

        $service = app(PostService::class);
        $service->create([
            'threads_account_id' => $account->id,
            'text' => 'A',
            'scheduled_at' => now()->addHour(),
        ]);
        $service->create([
            'threads_account_id' => $other->id,
            'text' => 'B',
            'scheduled_at' => now()->addHour(),
        ]);

        $result = $service->list(['threads_account_id' => $account->id]);

        $this->assertCount(1, $result);
        $this->assertSame('A', $result->first()->text);
    }

    public function test_find_returns_null_for_missing_post(): void
    {
        $this->assertNull(app(PostService::class)->find(999999));
    }
}
```

- [ ] **步驟 6.2：建立 `ReplyServiceTest.php`**

```php
<?php

namespace Tests\Feature;

use App\Enums\ReplySource;
use App\Enums\ReplyStatus;
use App\Models\ThreadsAccount;
use App\Services\ReplyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReplyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_sets_manual_source_and_new_status(): void
    {
        $account = ThreadsAccount::factory()->create();

        $reply = app(ReplyService::class)->create([
            'threads_account_id' => $account->id,
            'author_username' => 'someuser',
            'text' => '測試回覆',
        ]);

        $this->assertDatabaseHas('replies', [
            'id' => $reply->id,
            'threads_account_id' => $account->id,
            'source' => ReplySource::Manual->value,
            'status' => ReplyStatus::New->value,
            'post_id' => null,
        ]);
    }

    public function test_list_filters_by_status(): void
    {
        $service = app(ReplyService::class);
        $service->create([
            'threads_account_id' => ThreadsAccount::factory()->create()->id,
            'author_username' => 'a',
            'text' => 'one',
        ]);
        $service->create([
            'threads_account_id' => ThreadsAccount::factory()->create()->id,
            'author_username' => 'b',
            'text' => 'two',
        ]);

        $result = $service->list(['status' => ReplyStatus::New->value]);

        $this->assertCount(2, $result);
    }
}
```

- [ ] **步驟 6.3：建立 `McpToolsTest.php`**

```php
<?php

namespace Tests\Feature;

use App\Mcp\Servers\ThreadsMcpServer;
use App\Mcp\Tools\CreatePostTool;
use App\Mcp\Tools\CreateReplyTool;
use App\Mcp\Tools\GetPostTool;
use App\Mcp\Tools\ListAccountsTool;
use App\Mcp\Tools\ListPostsTool;
use App\Mcp\Tools\ListRepliesTool;
use App\Models\Post;
use App\Models\Reply;
use App\Models\ThreadsAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class McpToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_accounts_returns_accounts(): void
    {
        $account = ThreadsAccount::factory()->create(['username' => 'alice']);

        ThreadsMcpServer::tool(ListAccountsTool::class)
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('accounts.0.username', 'alice')
                ->where('accounts.0.status', 'active')
                ->etc());
    }

    public function test_list_accounts_filters_by_status(): void
    {
        ThreadsAccount::factory()->create();
        ThreadsAccount::factory()->needsReauth()->create();

        ThreadsMcpServer::tool(ListAccountsTool::class, ['status' => 'needs_reauth'])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('accounts', fn ($accounts) => count($accounts) === 1)
                ->etc());
    }

    public function test_create_post_requires_fields(): void
    {
        ThreadsMcpServer::tool(CreatePostTool::class, [])
            ->assertHasErrors();
    }

    public function test_create_post_creates_scheduled_post(): void
    {
        $account = ThreadsAccount::factory()->create();

        ThreadsMcpServer::tool(CreatePostTool::class, [
            'threads_account_id' => $account->id,
            'text' => '來自 MCP 的貼文',
            'scheduled_at' => now()->addHour()->toIso8601String(),
        ])->assertOk();

        $this->assertDatabaseHas('posts', [
            'threads_account_id' => $account->id,
            'text' => '來自 MCP 的貼文',
        ]);
    }

    public function test_list_posts_returns_posts(): void
    {
        Post::factory()->create(['text' => '第一篇']);

        ThreadsMcpServer::tool(ListPostsTool::class)
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('posts', fn ($posts) => count($posts) === 1)
                ->etc());
    }

    public function test_get_post_returns_post(): void
    {
        $post = Post::factory()->create(['text' => '查詢我']);

        ThreadsMcpServer::tool(GetPostTool::class, ['post_id' => $post->id])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('post.text', '查詢我')
                ->etc());
    }

    public function test_get_post_returns_error_when_missing(): void
    {
        ThreadsMcpServer::tool(GetPostTool::class, ['post_id' => 999999])
            ->assertHasErrors();
    }

    public function test_list_replies_returns_replies(): void
    {
        Reply::factory()->create(['text' => '一則回覆']);

        ThreadsMcpServer::tool(ListRepliesTool::class)
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('replies', fn ($replies) => count($replies) === 1)
                ->etc());
    }

    public function test_create_reply_creates_manual_reply(): void
    {
        $account = ThreadsAccount::factory()->create();

        ThreadsMcpServer::tool(CreateReplyTool::class, [
            'threads_account_id' => $account->id,
            'author_username' => 'commenter',
            'text' => '來自 MCP 的回覆',
        ])->assertOk();

        $this->assertDatabaseHas('replies', [
            'threads_account_id' => $account->id,
            'author_username' => 'commenter',
            'text' => '來自 MCP 的回覆',
            'source' => 'manual',
            'status' => 'new',
        ]);
    }

    public function test_create_reply_without_post_sets_null_post_id(): void
    {
        $account = ThreadsAccount::factory()->create();

        ThreadsMcpServer::tool(CreateReplyTool::class, [
            'threads_account_id' => $account->id,
            'author_username' => 'commenter',
            'text' => '無貼文回覆',
        ])->assertOk();

        $this->assertDatabaseHas('replies', [
            'threads_account_id' => $account->id,
            'text' => '無貼文回覆',
            'post_id' => null,
        ]);
    }
}
```

- [ ] **步驟 6.4：執行測試**

運行：`php artisan test --compact tests/Feature/PostServiceTest.php tests/Feature/ReplyServiceTest.php tests/Feature/McpToolsTest.php`
預期：全部通過。

---

### 任務 7：更新 `AGENTS.md` 加入 MCP 規範

**檔案：**
- 修改：`AGENTS.md`

- [ ] **步驟 7.1：新增 MCP 開發規範區段**

在 `AGENTS.md` 加入一段「MCP 開發」規範，內容包含：

```markdown
# MCP 開發

- 專案使用 `laravel/mcp` 建立 MCP 伺服器，伺服器類別位於 `app/Mcp/Servers/`，工具位於 `app/Mcp/Tools/`。
- MCP 伺服器於 `routes/ai.php` 同時以 `Mcp::local`（本地 Artisan）與 `Mcp::web`（HTTP + `auth:api`）註冊。
- HTTP 模式使用 Laravel Passport OAuth 保護，透過 `Mcp::oauthRoutes()` 註冊 OAuth2 路由。
- MCP 工具的業務邏輯必須收斂到 `app/Services/` 下的共用 Service，與後台介面遵循相同規則。
- **MCP 不包含帳號綁定（OAuth 授權）操作**；綁定僅能於後台介面完成。
- 工具回傳一律使用 `Response::structured([...])`，且不得直接 `toArray()` 洩漏敏感欄位（如 `access_token`）。
```

- [ ] **步驟 7.2：確認整體格式**

運行：`vendor/bin/pint --dirty --format agent`（若涉及 PHP）

---

### 任務 8：最終收斂

- [ ] **步驟 8.1：執行完整測試**

運行：`php artisan test --compact`
預期：既有測試與新測試全部通過。

- [ ] **步驟 8.2：確認路由**

運行：`php artisan route:list --path=mcp`
預期：顯示 `/mcp/threads` POST 路由。

- [ ] **步驟 8.3：回報完成**

依使用者偏好，提供「建議的 commit 訊息」與「變更檔案清單」，不主動 commit。

# 新增 Threads Webhook 回呼 實現計畫

> **面向 AI 代理的工作者：** 必需子技能：使用 superpowers:subagent-driven-development（推薦）或 superpowers:executing-plans 逐任務實現此計畫。步驟使用複選框（`- [ ]`）語法來追蹤進度。

**目標：** 新增 Threads Webhook 接收端點，讓系統在 Threads 產生新回覆時即時收到通知並建立回覆記錄。

**架構：** `ThreadsWebhookController` 處理 HTTP 層（`GET` 訂閱驗證、`POST` 事件接收），業務邏輯收斂至 `ThreadsWebhookService`。事件依 `field` 分派，回覆事件以 `threads_reply_id` 為唯一鍵 `firstOrCreate` 建立回覆，來源標記 `webhook`。

**技術棧：** Laravel 13、PHP 8.4、PHPUnit、SQLite

---

## 檔案結構

- 建立：`app/Http/Controllers/ThreadsWebhookController.php` — HTTP 層，處理訂閱驗證與事件接收。
- 建立：`app/Services/ThreadsWebhookService.php` — 業務層，事件分派與回覆建立。
- 建立：`tests/Feature/ThreadsWebhookTest.php` — Webhook 端點與 Service 的 feature 測試。
- 修改：`routes/web.php` — 新增 `GET/POST /threads/webhook` 路由。
- 修改：`config/services.php` — `threads` 區塊新增 `webhook_verify_token`。
- 修改：`.env.example` — 新增 `THREADS_WEBHOOK_VERIFY_TOKEN` 設定範例。

---

### 任務 1：設定與路由

**檔案：**
- 修改：`config/services.php:38-42`
- 修改：`.env.example`
- 修改：`routes/web.php:10-13`

- [ ] **步驟 1：在 `config/services.php` 新增 `webhook_verify_token`**

在 `threads` 區塊加入：

```php
'threads' => [
    'client_id' => env('THREADS_CLIENT_ID'),
    'client_secret' => env('THREADS_CLIENT_SECRET'),
    'redirect_uri' => rtrim((string) config('app.url'), '/').'/threads/oauth/callback',
    'webhook_verify_token' => env('THREADS_WEBHOOK_VERIFY_TOKEN'),
],
```

- [ ] **步驟 2：在 `.env.example` 新增設定範例**

在 Threads 憑證區塊加入：

```env
THREADS_WEBHOOK_VERIFY_TOKEN=你的Webhook驗證Token
```

- [ ] **步驟 3：在 `routes/web.php` 新增路由**

在 `threads/oauth` 群組後加入：

```php
Route::prefix('threads')->group(function () {
    Route::match(['get', 'post'], 'webhook', [ThreadsWebhookController::class, 'handle'])->name('threads.webhook');
});
```

並在檔案頂部加入 `use App\Http\Controllers\ThreadsWebhookController;`。

- [ ] **步驟 4：Commit**

```bash
git add config/services.php .env.example routes/web.php
git commit -m "feat: add threads webhook route and config"
```

---

### 任務 2：實作 `ThreadsWebhookService`

**檔案：**
- 建立：`app/Services/ThreadsWebhookService.php`
- 測試：`tests/Feature/ThreadsWebhookTest.php`

- [ ] **步驟 1：編寫失敗的測試**

在 `tests/Feature/ThreadsWebhookTest.php` 加入 Service 層測試：

```php
<?php

namespace Tests\Feature;

use App\Enums\ReplySource;
use App\Enums\ReplyStatus;
use App\Models\Post;
use App\Models\Reply;
use App\Models\ThreadsAccount;
use App\Services\ThreadsWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThreadsWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_reply_event_creates_reply(): void
    {
        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->published()->create([
            'threads_account_id' => $account->id,
            'threads_media_id' => 'media-1',
        ]);

        $service = app(ThreadsWebhookService::class);
        $service->handleEvent([
            'entry' => [[
                'changes' => [[
                    'field' => 'replies',
                    'value' => [
                        'media_id' => 'media-1',
                        'reply_id' => 'reply-1',
                        'text' => 'hi',
                        'username' => 'user1',
                    ],
                ]],
            ]],
        ]);

        $this->assertDatabaseHas('replies', [
            'threads_reply_id' => 'reply-1',
            'threads_account_id' => $account->id,
            'post_id' => $post->id,
            'user_id' => $account->user_id,
            'source' => ReplySource::Webhook->value,
            'status' => ReplyStatus::New->value,
        ]);
    }

    public function test_reply_event_marks_unread(): void
    {
        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->published()->create([
            'threads_account_id' => $account->id,
            'threads_media_id' => 'media-1',
        ]);

        $service = app(ThreadsWebhookService::class);
        $service->handleEvent([
            'entry' => [[
                'changes' => [[
                    'field' => 'replies',
                    'value' => [
                        'media_id' => 'media-1',
                        'reply_id' => 'reply-unread',
                        'text' => 'hi',
                        'username' => 'user1',
                    ],
                ]],
            ]],
        ]);

        $reply = Reply::query()->where('threads_reply_id', 'reply-unread')->firstOrFail();
        $this->assertNull($reply->read_at);
    }

    public function test_duplicate_reply_event_not_duplicated(): void
    {
        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->published()->create([
            'threads_account_id' => $account->id,
            'threads_media_id' => 'media-1',
        ]);

        Reply::factory()->create([
            'threads_account_id' => $account->id,
            'post_id' => $post->id,
            'threads_reply_id' => 'reply-existing',
        ]);

        $service = app(ThreadsWebhookService::class);
        $service->handleEvent([
            'entry' => [[
                'changes' => [[
                    'field' => 'replies',
                    'value' => [
                        'media_id' => 'media-1',
                        'reply_id' => 'reply-existing',
                        'text' => 'hi',
                        'username' => 'user1',
                    ],
                ]],
            ]],
        ]);

        $this->assertSame(1, Reply::query()->count());
    }

    public function test_unmatched_event_is_skipped(): void
    {
        $service = app(ThreadsWebhookService::class);
        $service->handleEvent([
            'entry' => [[
                'changes' => [[
                    'field' => 'replies',
                    'value' => [
                        'media_id' => 'unknown-media',
                        'reply_id' => 'reply-orphan',
                        'text' => 'hi',
                        'username' => 'user1',
                    ],
                ]],
            ]],
        ]);

        $this->assertSame(0, Reply::query()->count());
    }
}
```

- [ ] **步驟 2：運行測試驗證失敗**

運行：`php artisan test --compact tests/Feature/ThreadsWebhookTest.php`
預期：FAIL，報錯 "Class ThreadsWebhookService not found"

- [ ] **步驟 3：編寫最少實現程式碼**

建立 `app/Services/ThreadsWebhookService.php`：

```php
<?php

namespace App\Services;

use App\Enums\ReplySource;
use App\Enums\ReplyStatus;
use App\Models\Post;
use App\Models\Reply;
use Illuminate\Support\Facades\Log;

class ThreadsWebhookService
{
    /**
     * 處理 Webhook 事件 payload，依 field 分派。
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleEvent(array $payload): void
    {
        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $this->dispatch($change['field'] ?? '', $change['value'] ?? []);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function dispatch(string $field, array $value): void
    {
        match ($field) {
            'replies' => $this->handleReplyCreated($value),
            default => Log::debug('Threads webhook: unsupported field', ['field' => $field]),
        };
    }

    /**
     * 處理回覆事件，以 threads_reply_id 為唯一鍵建立回覆。
     *
     * @param  array<string, mixed>  $value
     */
    private function handleReplyCreated(array $value): void
    {
        $mediaId = $value['media_id'] ?? null;
        $replyId = $value['reply_id'] ?? null;

        if ($mediaId === null || $replyId === null) {
            Log::warning('Threads webhook: reply event missing media_id or reply_id', ['value' => $value]);

            return;
        }

        $post = Post::query()
            ->where('threads_media_id', $mediaId)
            ->first();

        if ($post === null) {
            Log::warning('Threads webhook: no matching post for media_id', ['media_id' => $mediaId]);

            return;
        }

        Reply::query()->firstOrCreate(
            ['threads_reply_id' => $replyId],
            [
                'user_id' => $post->user_id,
                'threads_account_id' => $post->threads_account_id,
                'post_id' => $post->id,
                'author_username' => $value['username'] ?? '',
                'text' => $value['text'] ?? '',
                'source' => ReplySource::Webhook,
                'status' => ReplyStatus::New,
            ],
        );
    }
}
```

- [ ] **步驟 4：運行測試驗證通過**

運行：`php artisan test --compact tests/Feature/ThreadsWebhookTest.php`
預期：PASS（4 個測試）

- [ ] **步驟 5：Commit**

```bash
git add app/Services/ThreadsWebhookService.php tests/Feature/ThreadsWebhookTest.php
git commit -m "feat: add ThreadsWebhookService to handle reply events"
```

---

### 任務 3：實作 `ThreadsWebhookController`

**檔案：**
- 建立：`app/Http/Controllers/ThreadsWebhookController.php`
- 測試：`tests/Feature/ThreadsWebhookTest.php`

- [ ] **步驟 1：編寫失敗的測試**

在 `tests/Feature/ThreadsWebhookTest.php` 加入端點測試：

```php
    public function test_verification_returns_challenge(): void
    {
        config(['services.threads.webhook_verify_token' => 'secret-token']);

        $response = $this->get('/threads/webhook?hub.mode=subscribe&hub.verify_token=secret-token&hub.challenge=abc123');

        $response->assertOk();
        $response->assertContent('abc123');
    }

    public function test_verification_rejects_wrong_token(): void
    {
        config(['services.threads.webhook_verify_token' => 'secret-token']);

        $response = $this->get('/threads/webhook?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=abc123');

        $response->assertForbidden();
    }

    public function test_post_event_creates_reply(): void
    {
        config(['services.threads.webhook_verify_token' => 'secret-token']);

        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->published()->create([
            'threads_account_id' => $account->id,
            'threads_media_id' => 'media-1',
        ]);

        $response = $this->postJson('/threads/webhook', [
            'entry' => [[
                'changes' => [[
                    'field' => 'replies',
                    'value' => [
                        'media_id' => 'media-1',
                        'reply_id' => 'reply-http',
                        'text' => 'hi',
                        'username' => 'user1',
                    ],
                ]],
            ]],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('replies', ['threads_reply_id' => 'reply-http']);
    }
```

- [ ] **步驟 2：運行測試驗證失敗**

運行：`php artisan test --compact tests/Feature/ThreadsWebhookTest.php`
預期：FAIL，報錯 "Class ThreadsWebhookController not found"

- [ ] **步驟 3：編寫最少實現程式碼**

建立 `app/Http/Controllers/ThreadsWebhookController.php`：

```php
<?php

namespace App\Http\Controllers;

use App\Services\ThreadsWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ThreadsWebhookController extends Controller
{
    public function __construct(private readonly ThreadsWebhookService $service) {}

    /**
     * 處理 Webhook 訂閱驗證（GET）與事件接收（POST）。
     */
    public function handle(Request $request): Response|JsonResponse
    {
        if ($request->isMethod('get')) {
            return $this->verify($request);
        }

        $this->service->handleEvent($request->all());

        return response()->json(['status' => 'ok']);
    }

    private function verify(Request $request): Response
    {
        $mode = $request->query('hub.mode');
        $token = $request->query('hub.verify_token');
        $challenge = $request->query('hub.challenge');

        $expected = config('services.threads.webhook_verify_token');

        if ($mode === 'subscribe' && $token === $expected && $challenge !== null) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }
}
```

- [ ] **步驟 4：運行測試驗證通過**

運行：`php artisan test --compact tests/Feature/ThreadsWebhookTest.php`
預期：PASS（7 個測試）

- [ ] **步驟 5：Commit**

```bash
git add app/Http/Controllers/ThreadsWebhookController.php tests/Feature/ThreadsWebhookTest.php
git commit -m "feat: add ThreadsWebhookController for verification and events"
```

---

### 任務 4：格式化與完整測試

**檔案：**
- 修改：所有新增/修改的 PHP 檔案

- [ ] **步驟 1：運行 Pint 格式化**

運行：`vendor/bin/pint --dirty --format agent`
預期：無錯誤，程式碼符合專案風格

- [ ] **步驟 2：運行完整測試套件**

運行：`php artisan test --compact`
預期：全部 PASS

- [ ] **步驟 3：Commit**

```bash
git add -A
git commit -m "chore: format code and verify full test suite"
```

# 回覆發佈對齊 實作計畫

> **面向 AI 代理的工作者：** 必需子技能：使用 superpowers:subagent-driven-development（推薦）或 superpowers:executing-plans 逐任務實作此計畫。步驟使用複選框（`- [ ]`）語法來追蹤進度。
>
> **Commit 說明：** 本專案由使用者手動 commit。執行時**不自動 commit**，改為在每個任務完成後標記檢查點，最終統一回報「建議 commit 訊息」與「變更檔案清單」。

**目標：** 讓「新增貼文回覆」與「回應回覆」都實際發佈到 Threads，並統一後台與 MCP 的名詞與行為。

**架構：** 新增 `ReplyStatus` 的 `publishing`／`failed` 狀態、`replies` 表的 `error_message`／`publish_attempts` 欄位；`ReplyService` 收斂「建立貼文回覆」與「回應回覆」邏輯；新增 `PublishReply` job 做兩階段非同步發佈（對齊 `PublishScheduledPost`）；後台與 MCP 名詞對齊。

**技術棧：** Laravel 11 / PHP 8.4 / Filament 4 / Spatie-Like Enum / Mockery / PHPUnit / SQLite

---

## 檔案結構總覽

**建立：**
- `app/Jobs/PublishReply.php` — 回覆兩階段發佈 job
- `database/migrations/2026_08_16_XXXXXX_add_publish_fields_to_replies_table.php` — 新增欄位 + 刪除歷史記錄
- `tests/Feature/PublishReplyTest.php` — job 測試

**修改：**
- `app/Enums/ReplyStatus.php` — 新增 `Publishing`／`Failed`，實作 `HasColor`／`HasLabel`
- `app/Models/Reply.php` — `$fillable`、`casts()` 納入新欄位
- `database/factories/ReplyFactory.php` — 補新欄位與 states
- `app/Services/ReplyService.php` — `createPostReply()`、`publish()`、`resolveReplyToId()`
- `app/Filament/Resources/Replies/Schemas/ReplyForm.php` — 移除 `author_username`、`post_id` 必填
- `app/Filament/Resources/Replies/Pages/CreateReply.php` — 走 `ReplyService`
- `app/Filament/Resources/Replies/Pages/ListReplies.php` — `CreateAction` label
- `app/Filament/Resources/Replies/Tables/RepliesTable.php` — 按鈕名詞、狀態顯示、action 收斂
- `app/Filament/Resources/Replies/Widgets/RepliesSyncNotice.php` — 傳遞延遲秒數
- `resources/views/filament/widgets/replies-sync-notice.blade.php` — 加延遲說明
- `app/Mcp/Tools/CreateReplyTool.php` — 參數與描述對齊
- `app/Mcp/Tools/ListRepliesTool.php` — 回傳新狀態
- `tests/Feature/ReplyServiceTest.php`、`tests/Feature/ReplyResourceTest.php`、`tests/Feature/McpToolsTest.php` — 對齊新行為

---

## 任務 1：擴充 `ReplyStatus` enum

**檔案：**
- 修改：`app/Enums/ReplyStatus.php`

`ReplyStatus` 目前是純 `enum`，而 `PostStatus` 已實作 `Filament\Support\Contracts\HasColor`／`HasLabel`。為對齊並讓列表 badge 自動顯示，本任務讓 `ReplyStatus` 也實作這兩個介面。

**語義對照（統一後）：**

| Case | value | label | color |
|---|---|---|---|
| New | `new` | 待處理 | warning |
| Publishing | `publishing` | 發佈中 | info |
| Replied | `replied` | 已回覆 | success |
| Failed | `failed` | 發佈失敗 | danger |
| Ignored | `ignored` | 已忽略 | gray |

- [ ] **步驟 1：編寫失敗的測試**

建立 `tests/Feature/ReplyStatusTest.php`：

```php
<?php

namespace Tests\Feature;

use App\Enums\ReplyStatus;
use Tests\TestCase;

class ReplyStatusTest extends TestCase
{
    public function test_has_publishing_and_failed_cases(): void
    {
        $this->assertSame('publishing', ReplyStatus::Publishing->value);
        $this->assertSame('failed', ReplyStatus::Failed->value);
    }

    public function test_labels(): void
    {
        $this->assertSame('待處理', ReplyStatus::New->getLabel());
        $this->assertSame('發佈中', ReplyStatus::Publishing->getLabel());
        $this->assertSame('已回覆', ReplyStatus::Replied->getLabel());
        $this->assertSame('發佈失敗', ReplyStatus::Failed->getLabel());
        $this->assertSame('已忽略', ReplyStatus::Ignored->getLabel());
    }

    public function test_colors(): void
    {
        $this->assertSame('warning', ReplyStatus::New->getColor());
        $this->assertSame('info', ReplyStatus::Publishing->getColor());
        $this->assertSame('success', ReplyStatus::Replied->getColor());
        $this->assertSame('danger', ReplyStatus::Failed->getColor());
        $this->assertSame('gray', ReplyStatus::Ignored->getColor());
    }
}
```

- [ ] **步驟 2：執行測試驗證失敗**

執行：`php artisan test --compact tests/Feature/ReplyStatusTest.php`
預期：FAIL，`ReplyStatus::Publishing` 不存在（`Undefined constant`）

- [ ] **步驟 3：編寫最少實作程式碼**

將 `app/Enums/ReplyStatus.php` 改為：

```php
<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ReplyStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case Publishing = 'publishing';
    case Replied = 'replied';
    case Failed = 'failed';
    case Ignored = 'ignored';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::New => '待處理',
            self::Publishing => '發佈中',
            self::Replied => '已回覆',
            self::Failed => '發佈失敗',
            self::Ignored => '已忽略',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::New => 'warning',
            self::Publishing => 'info',
            self::Replied => 'success',
            self::Failed => 'danger',
            self::Ignored => 'gray',
        };
    }
}
```

- [ ] **步驟 4：執行測試驗證通過**

執行：`php artisan test --compact tests/Feature/ReplyStatusTest.php`
預期：PASS

- [ ] **步驟 5：檢查點**

確認 `ReplyStatus` 已實作 `HasColor`／`HasLabel`，5 個 case 齊全。

---

## 任務 2：`replies` 表 migration

**檔案：**
- 建立：`database/migrations/2026_08_16_170000_add_publish_fields_to_replies_table.php`

新增 `error_message`、`publish_attempts` 欄位，並刪除舊語義下產生的 `source=manual` 且 `threads_reply_id IS NULL` 歷史記錄。

- [ ] **步驟 1：編寫 migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('replies', function (Blueprint $table) {
            $table->text('error_message')->nullable()->after('status');
            $table->unsignedInteger('publish_attempts')->default(0)->after('error_message');
        });

        DB::table('replies')
            ->where('source', 'manual')
            ->whereNull('threads_reply_id')
            ->delete();
    }

    public function down(): void
    {
        Schema::table('replies', function (Blueprint $table) {
            $table->dropColumn(['error_message', 'publish_attempts']);
        });
    }
};
```

- [ ] **步驟 2：執行 migration 並驗證**

執行：`php artisan migrate`
預期：成功執行，無錯誤。

執行：`php artisan tinker --execute 'dump(App\Models\Reply::query()->where("source", "manual")->whereNull("threads_reply_id")->count());'`
預期：輸出 `0`（歷史記錄已刪除）。

- [ ] **步驟 3：檢查點**

確認新欄位存在（`error_message`、`publish_attempts`），歷史 manual 記錄已刪除。

---

## 任務 3：更新 `Reply` model 與 `ReplyFactory`

**檔案：**
- 修改：`app/Models/Reply.php`
- 修改：`database/factories/ReplyFactory.php`

- [ ] **步驟 1：更新 `Reply` model**

將 `app/Models/Reply.php` 的 `$fillable` 與 `casts()` 加入新欄位：

```php
protected $fillable = [
    'threads_account_id',
    'post_id',
    'threads_reply_id',
    'author_username',
    'text',
    'source',
    'status',
    'error_message',
    'publish_attempts',
    'replied_at',
];

protected function casts(): array
{
    return [
        'source' => ReplySource::class,
        'status' => ReplyStatus::class,
        'replied_at' => 'datetime',
        'publish_attempts' => 'integer',
    ];
}
```

- [ ] **步驟 2：更新 `ReplyFactory`**

在 `database/factories/ReplyFactory.php` 的 `definition()` 加入新欄位預設值，並新增 `publishing`／`failed` states：

```php
public function definition(): array
{
    return [
        'threads_account_id' => ThreadsAccount::factory(),
        'post_id' => null,
        'threads_reply_id' => fake()->unique()->numerify('##########'),
        'author_username' => fake()->userName(),
        'text' => fake()->sentence(),
        'source' => ReplySource::Polling,
        'status' => ReplyStatus::New,
        'error_message' => null,
        'publish_attempts' => 0,
        'replied_at' => null,
    ];
}

public function publishing(): static
{
    return $this->state(fn (array $attributes) => [
        'status' => ReplyStatus::Publishing,
    ]);
}

public function failed(): static
{
    return $this->state(fn (array $attributes) => [
        'status' => ReplyStatus::Failed,
        'error_message' => '發佈失敗',
    ]);
}
```

- [ ] **步驟 3：執行既有測試確認無回歸**

執行：`php artisan test --compact tests/Feature/ReplyServiceTest.php`
預期：PASS（既有測試仍通過，因為 model 變更向後相容）

- [ ] **步驟 4：檢查點**

`Reply` 可寫入 `error_message`、`publish_attempts`，factory 有 `publishing`／`failed` states。

---

## 任務 4：`ReplyService` 新增建立貼文回覆與發佈方法

**檔案：**
- 修改：`app/Services/ReplyService.php`

將既有 `create(array $data)` 重構為 `createPostReply(int, int, string)`，新增 `publish(Reply, string)` 與 `resolveReplyToId(Reply)`。既有 `create()` 移除（其語義被 `createPostReply` 取代）。

- [ ] **步驟 1：編寫失敗的測試**

重寫 `tests/Feature/ReplyServiceTest.php`：

```php
<?php

namespace Tests\Feature;

use App\Enums\ReplySource;
use App\Enums\ReplyStatus;
use App\Jobs\PublishReply;
use App\Models\Post;
use App\Models\Reply;
use App\Models\ThreadsAccount;
use App\Services\ReplyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use Tests\TestCase;

class ReplyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_post_reply_dispatches_publish_job(): void
    {
        Queue::fake();

        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->published()->create(['threads_account_id' => $account->id]);

        $reply = app(ReplyService::class)->createPostReply($account->id, $post->id, '貼文回覆內容');

        $this->assertDatabaseHas('replies', [
            'id' => $reply->id,
            'threads_account_id' => $account->id,
            'post_id' => $post->id,
            'threads_reply_id' => null,
            'text' => '貼文回覆內容',
            'source' => ReplySource::Manual->value,
            'status' => ReplyStatus::New->value,
        ]);

        Queue::assertPushed(PublishReply::class, fn ($job) => $job->replyId === $reply->id);
    }

    public function test_create_post_reply_rejects_unpublished_post(): void
    {
        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->create(['threads_account_id' => $account->id, 'threads_media_id' => null]);

        $this->expectException(InvalidArgumentException::class);

        app(ReplyService::class)->createPostReply($account->id, $post->id, '內容');
    }

    public function test_publish_dispatches_job_with_text(): void
    {
        Queue::fake();

        $account = ThreadsAccount::factory()->create();
        $reply = Reply::factory()->create([
            'threads_account_id' => $account->id,
            'threads_reply_id' => '12345',
            'status' => ReplyStatus::New,
        ]);

        app(ReplyService::class)->publish($reply, '回應內容');

        Queue::assertPushed(PublishReply::class, function ($job) use ($reply) {
            return $job->replyId === $reply->id && $job->replyText === '回應內容';
        });
    }

    public function test_publish_rejects_reply_without_threads_id(): void
    {
        $account = ThreadsAccount::factory()->create();
        $reply = Reply::factory()->create([
            'threads_account_id' => $account->id,
            'threads_reply_id' => null,
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(ReplyService::class)->publish($reply, '回應內容');
    }

    public function test_resolve_reply_to_id_returns_threads_reply_id_when_present(): void
    {
        $account = ThreadsAccount::factory()->create();
        $reply = Reply::factory()->create([
            'threads_account_id' => $account->id,
            'threads_reply_id' => 'comment-id-123',
        ]);

        $this->assertSame('comment-id-123', app(ReplyService::class)->resolveReplyToId($reply));
    }

    public function test_resolve_reply_to_id_returns_post_media_id_when_reply_id_null(): void
    {
        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->published()->create(['threads_account_id' => $account->id]);
        $reply = Reply::factory()->create([
            'threads_account_id' => $account->id,
            'post_id' => $post->id,
            'threads_reply_id' => null,
        ]);

        $this->assertSame($post->threads_media_id, app(ReplyService::class)->resolveReplyToId($reply));
    }

    public function test_list_filters_by_status(): void
    {
        $service = app(ReplyService::class);
        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->published()->create(['threads_account_id' => $account->id]);

        $service->createPostReply($account->id, $post->id, 'one');
        $service->createPostReply($account->id, $post->id, 'two');

        $this->assertCount(2, $service->list(['status' => ReplyStatus::New->value]));
    }
}
```

- [ ] **步驟 2：執行測試驗證失敗**

執行：`php artisan test --compact tests/Feature/ReplyServiceTest.php`
預期：FAIL，`createPostReply`、`publish`、`resolveReplyToId` 方法不存在。

- [ ] **步驟 3：編寫最少實作程式碼**

將 `app/Services/ReplyService.php` 改為：

```php
<?php

namespace App\Services;

use App\Enums\ReplySource;
use App\Enums\ReplyStatus;
use App\Jobs\PublishReply;
use App\Models\Post;
use App\Models\Reply;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class ReplyService
{
    /**
     * 建立一筆貼文回覆並排程發佈到 Threads。
     */
    public function createPostReply(int $threadsAccountId, int $postId, string $text): Reply
    {
        $post = Post::query()->find($postId);

        if ($post === null || $post->threads_media_id === null) {
            throw new InvalidArgumentException('目標貼文不存在或尚未發佈，無法回覆');
        }

        $reply = new Reply;
        $reply->threads_account_id = $threadsAccountId;
        $reply->post_id = $postId;
        $reply->threads_reply_id = null;
        $reply->author_username = '';
        $reply->text = $text;
        $reply->source = ReplySource::Manual;
        $reply->status = ReplyStatus::New;
        $reply->save();

        PublishReply::dispatch($reply->id);

        return $reply;
    }

    /**
     * 回應一則留言並排程發佈到 Threads。
     */
    public function publish(Reply $reply, string $text): void
    {
        if ($reply->threads_reply_id === null) {
            throw new InvalidArgumentException('該留言缺少 Threads ID，無法回應');
        }

        PublishReply::dispatch($reply->id, null, $text);
    }

    /**
     * 推導回覆的發佈目標 ID（回覆留言或回覆貼文）。
     */
    public function resolveReplyToId(Reply $reply): string
    {
        if ($reply->threads_reply_id !== null) {
            return $reply->threads_reply_id;
        }

        $post = $reply->post;

        if ($post === null || $post->threads_media_id === null) {
            throw new InvalidArgumentException('無法決定回覆目標');
        }

        return $post->threads_media_id;
    }

    /**
     * 查詢回覆清單，支援依帳號、貼文與狀態篩選。
     *
     * @param  array{threads_account_id?: int, post_id?: int, status?: string}  $filters
     * @return Collection<int, Reply>
     */
    public function list(array $filters = []): Collection
    {
        $query = Reply::query()->with(['threadsAccount', 'post']);

        if (! empty($filters['threads_account_id'])) {
            $query->where('threads_account_id', $filters['threads_account_id']);
        }

        if (! empty($filters['post_id'])) {
            $query->where('post_id', $filters['post_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('created_at')->get();
    }
}
```

- [ ] **步驟 4：執行測試驗證通過**

執行：`php artisan test --compact tests/Feature/ReplyServiceTest.php`
預期：PASS（注意：此測試依賴 `PublishReply` job 存在，任務 5 尚未建立，會先 FAIL——請連同任務 5 一起驗證，或先建一個空 `PublishReply` 骨架）。

- [ ] **步驟 5：檢查點**

`ReplyService` 三個方法就緒，`create()` 已移除。

> **注意：** 步驟 4 會因 `PublishReply` 尚未建立而失敗。請先完成任務 5 的 job 骨架後再一起跑測試，或先建立空的 `PublishReply` class 讓測試能引用。

---

## 任務 5：新增 `PublishReply` job

**檔案：**
- 建立：`app/Jobs/PublishReply.php`
- 建立：`tests/Feature/PublishReplyTest.php`

對齊 `PublishScheduledPost`：兩階段（`createTextContainer` → 延遲 → `publishContainer`）、重試、token 失效、限流處理。發佈延遲引用 `PublishScheduledPost::PUBLISH_DELAY_SECONDS`。

- [ ] **步驟 1：建立 job 骨架（供任務 4 測試引用）**

建立 `app/Jobs/PublishReply.php`：

```php
<?php

namespace App\Jobs;

use App\Enums\ReplyStatus;
use App\Enums\ThreadsAccountStatus;
use App\Exceptions\ThreadsApiException;
use App\Models\Reply;
use App\Services\ReplyService;
use App\Services\ThreadsClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PublishReply implements ShouldQueue
{
    use Queueable;

    /**
     * Maximum number of publish attempts before marking a reply as failed.
     */
    public const MAX_PUBLISH_ATTEMPTS = 3;

    /**
     * Base backoff (in seconds) multiplied by the attempt number.
     */
    public const RETRY_BACKOFF_SECONDS = 60;

    public function __construct(
        public readonly int $replyId,
        public readonly ?string $creationId = null,
        public readonly ?string $replyText = null,
    ) {}

    public function handle(ThreadsClient $threads, ReplyService $replies): void
    {
        $reply = Reply::query()->find($this->replyId);

        $expectedStatus = $this->creationId === null
            ? ReplyStatus::New
            : ReplyStatus::Publishing;

        if ($reply === null || $reply->status !== $expectedStatus) {
            return;
        }

        $account = $reply->threadsAccount;

        if ($account === null) {
            return;
        }

        try {
            if ($this->creationId === null) {
                $text = $this->replyText ?? $reply->text;
                $replyToId = $replies->resolveReplyToId($reply);

                $creationId = $threads->createTextContainer($account, $text, $replyToId);
                $reply->update(['status' => ReplyStatus::Publishing]);

                static::dispatch($this->replyId, $creationId)
                    ->delay(now()->addSeconds(\App\Jobs\PublishScheduledPost::PUBLISH_DELAY_SECONDS));

                return;
            }

            $threads->publishContainer($account, $this->creationId);

            $reply->update([
                'status' => ReplyStatus::Replied,
                'replied_at' => now(),
                'error_message' => null,
            ]);
        } catch (ThreadsApiException $e) {
            if ($e->isTokenInvalid()) {
                $account->update(['status' => ThreadsAccountStatus::NeedsReauth]);
                $reply->update([
                    'status' => ReplyStatus::Failed,
                    'error_message' => 'token 失效',
                ]);
            } elseif ($e->isRateLimited()) {
                $reply->update([
                    'status' => ReplyStatus::Failed,
                    'error_message' => '已達每日發文上限',
                ]);
            } elseif ($e->isRetryable() && $reply->publish_attempts < self::MAX_PUBLISH_ATTEMPTS) {
                $attempt = $reply->publish_attempts + 1;
                $reply->update(['publish_attempts' => $attempt]);

                static::dispatch($this->replyId, $this->creationId, $this->replyText)
                    ->delay(now()->addSeconds($attempt * self::RETRY_BACKOFF_SECONDS));
            } else {
                $reply->update([
                    'status' => ReplyStatus::Failed,
                    'error_message' => $e->getMessage(),
                ]);
            }
        } catch (\Throwable $e) {
            $reply->update([
                'status' => ReplyStatus::Failed,
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Threads reply publish failed', [
                'reply_id' => $reply->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

- [ ] **步驟 2：編寫 job 測試**

建立 `tests/Feature/PublishReplyTest.php`：

```php
<?php

namespace Tests\Feature;

use App\Enums\ReplyStatus;
use App\Enums\ThreadsAccountStatus;
use App\Exceptions\ThreadsApiException;
use App\Jobs\PublishReply;
use App\Models\Post;
use App\Models\Reply;
use App\Models\ThreadsAccount;
use App\Services\ThreadsClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class PublishReplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_stage_creates_container_and_sets_publishing(): void
    {
        Queue::fake();

        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->published()->create(['threads_account_id' => $account->id]);
        $reply = Reply::factory()->create([
            'threads_account_id' => $account->id,
            'post_id' => $post->id,
            'threads_reply_id' => null,
            'status' => ReplyStatus::New,
            'text' => '回覆內容',
        ]);

        $threads = Mockery::mock(ThreadsClient::class);
        $threads->shouldReceive('createTextContainer')
            ->once()
            ->with($account, '回覆內容', $post->threads_media_id)
            ->andReturn('creation-id-123');
        $threads->shouldReceive('publishContainer')->never();

        $job = new PublishReply($reply->id);
        $job->handle($threads, app(\App\Services\ReplyService::class));

        $reply->refresh();

        $this->assertSame(ReplyStatus::Publishing, $reply->status);
        Queue::assertPushed(PublishReply::class, 1);
    }

    public function test_successful_publish_marks_replied(): void
    {
        $account = ThreadsAccount::factory()->create();
        $reply = Reply::factory()->create([
            'threads_account_id' => $account->id,
            'status' => ReplyStatus::Publishing,
        ]);

        $threads = Mockery::mock(ThreadsClient::class);
        $threads->shouldReceive('publishContainer')->once()->andReturn('media-id-123');

        $job = new PublishReply($reply->id, 'creation-id');
        $job->handle($threads, app(\App\Services\ReplyService::class));

        $reply->refresh();

        $this->assertSame(ReplyStatus::Replied, $reply->status);
        $this->assertNotNull($reply->replied_at);
    }

    public function test_token_invalid_marks_account_needs_reauth(): void
    {
        $account = ThreadsAccount::factory()->create();
        $reply = Reply::factory()->create([
            'threads_account_id' => $account->id,
            'status' => ReplyStatus::Publishing,
        ]);

        $threads = Mockery::mock(ThreadsClient::class);
        $threads->shouldReceive('publishContainer')
            ->once()
            ->andThrow(new ThreadsApiException('Invalid OAuth access token', 190, 401));

        $job = new PublishReply($reply->id, 'creation-id');
        $job->handle($threads, app(\App\Services\ReplyService::class));

        $reply->refresh();
        $account->refresh();

        $this->assertSame(ReplyStatus::Failed, $reply->status);
        $this->assertSame('token 失效', $reply->error_message);
        $this->assertSame(ThreadsAccountStatus::NeedsReauth, $account->status);
    }

    public function test_rate_limit_marks_reply_failed(): void
    {
        $account = ThreadsAccount::factory()->create();
        $reply = Reply::factory()->create([
            'threads_account_id' => $account->id,
            'status' => ReplyStatus::Publishing,
        ]);

        $threads = Mockery::mock(ThreadsClient::class);
        $threads->shouldReceive('publishContainer')
            ->once()
            ->andThrow(new ThreadsApiException('Application request limit reached', 4, 429));

        $job = new PublishReply($reply->id, 'creation-id');
        $job->handle($threads, app(\App\Services\ReplyService::class));

        $reply->refresh();

        $this->assertSame(ReplyStatus::Failed, $reply->status);
        $this->assertSame('已達每日發文上限', $reply->error_message);
    }

    public function test_retryable_error_redispatches_and_increments_attempts(): void
    {
        Queue::fake();

        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->published()->create(['threads_account_id' => $account->id]);
        $reply = Reply::factory()->create([
            'threads_account_id' => $account->id,
            'post_id' => $post->id,
            'threads_reply_id' => null,
            'status' => ReplyStatus::New,
            'publish_attempts' => 0,
        ]);

        $threads = Mockery::mock(ThreadsClient::class);
        $threads->shouldReceive('createTextContainer')
            ->once()
            ->andThrow(new ThreadsApiException('The requested resource does not exist', null, null));

        $job = new PublishReply($reply->id);
        $job->handle($threads, app(\App\Services\ReplyService::class));

        $reply->refresh();

        $this->assertSame(1, $reply->publish_attempts);
        $this->assertSame(ReplyStatus::New, $reply->status);
        Queue::assertPushed(PublishReply::class, 1);
    }

    public function test_retryable_error_at_max_attempts_marks_failed(): void
    {
        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->published()->create(['threads_account_id' => $account->id]);
        $reply = Reply::factory()->create([
            'threads_account_id' => $account->id,
            'post_id' => $post->id,
            'threads_reply_id' => null,
            'status' => ReplyStatus::New,
            'publish_attempts' => PublishReply::MAX_PUBLISH_ATTEMPTS,
        ]);

        $threads = Mockery::mock(ThreadsClient::class);
        $threads->shouldReceive('createTextContainer')
            ->once()
            ->andThrow(new ThreadsApiException('The requested resource does not exist', null, null));

        $job = new PublishReply($reply->id);
        $job->handle($threads, app(\App\Services\ReplyService::class));

        $reply->refresh();

        $this->assertSame(ReplyStatus::Failed, $reply->status);
        $this->assertSame('The requested resource does not exist', $reply->error_message);
    }

    public function test_non_new_reply_is_skipped(): void
    {
        $account = ThreadsAccount::factory()->create();
        $reply = Reply::factory()->replied()->create(['threads_account_id' => $account->id]);

        $threads = Mockery::mock(ThreadsClient::class);
        $threads->shouldReceive('publishContainer')->never();

        $job = new PublishReply($reply->id, 'creation-id');
        $job->handle($threads, app(\App\Services\ReplyService::class));

        $reply->refresh();

        $this->assertSame(ReplyStatus::Replied, $reply->status);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

- [ ] **步驟 3：執行測試驗證通過**

執行：`php artisan test --compact tests/Feature/PublishReplyTest.php tests/Feature/ReplyServiceTest.php`
預期：PASS

- [ ] **步驟 4：檢查點**

`PublishReply` 兩階段發佈、錯誤處理、重試皆就緒。

---

## 任務 6：後台表單與建立頁面

**檔案：**
- 修改：`app/Filament/Resources/Replies/Schemas/ReplyForm.php`
- 修改：`app/Filament/Resources/Replies/Pages/CreateReply.php`

- [ ] **步驟 1：更新 `ReplyForm`**

將 `app/Filament/Resources/Replies/Schemas/ReplyForm.php` 的 `configure()` 改為（移除 `author_username`、`post_id` 必填且只列出已發佈貼文）：

```php
public static function configure(Schema $schema): Schema
{
    return $schema
        ->components([
            Select::make('threads_account_id')
                ->label('來源帳號')
                ->relationship('threadsAccount', 'username')
                ->getOptionLabelFromRecordUsing(fn ($record) => "@{$record->username}")
                ->required(),

            Select::make('post_id')
                ->label('目標貼文')
                ->relationship('post', 'text', fn ($query) => $query->whereNotNull('threads_media_id'))
                ->getOptionLabelFromRecordUsing(fn ($record) => mb_strimwidth($record->text, 0, 40, '...'))
                ->required(),

            Textarea::make('text')
                ->label('回覆內容')
                ->required()
                ->maxLength(500)
                ->rows(4),
        ]);
}
```

- [ ] **步驟 2：更新 `CreateReply` 頁面走 Service**

將 `app/Filament/Resources/Replies/Pages/CreateReply.php` 改為（移除 `mutateFormDataBeforeCreate`，改覆寫 `handleRecordCreation` 呼叫 Service）：

```php
<?php

namespace App\Filament\Resources\Replies\Pages;

use App\Filament\Resources\Replies\ReplyResource;
use App\Models\Reply;
use App\Services\ReplyService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateReply extends CreateRecord
{
    protected static string $resource = ReplyResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(ReplyService::class)->createPostReply(
            (int) $data['threads_account_id'],
            (int) $data['post_id'],
            $data['text'],
        );
    }
}
```

- [ ] **步驟 3：更新 `ReplyResourceTest`**

重寫 `tests/Feature/ReplyResourceTest.php`：

```php
<?php

namespace Tests\Feature;

use App\Enums\ReplySource;
use App\Enums\ReplyStatus;
use App\Filament\Resources\Replies\Pages\CreateReply;
use App\Filament\Resources\Replies\Pages\ListReplies;
use App\Models\Post;
use App\Models\Reply;
use App\Models\ThreadsAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class ReplyResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_reply_with_valid_data(): void
    {
        Queue::fake();

        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->published()->create(['threads_account_id' => $account->id]);

        Livewire::actingAs(User::factory()->create())
            ->test(CreateReply::class)
            ->fillForm([
                'threads_account_id' => $account->id,
                'post_id' => $post->id,
                'text' => '這是一則測試回覆',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('replies', [
            'threads_account_id' => $account->id,
            'text' => '這是一則測試回覆',
            'source' => ReplySource::Manual->value,
            'status' => ReplyStatus::New->value,
            'post_id' => $post->id,
        ]);
    }

    public function test_create_reply_rejects_missing_required_fields(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreateReply::class)
            ->fillForm([
                'threads_account_id' => null,
                'post_id' => null,
                'text' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'threads_account_id' => 'required',
                'post_id' => 'required',
                'text' => 'required',
            ]);
    }

    public function test_list_replies_shows_records(): void
    {
        $account = ThreadsAccount::factory()->create();
        $replies = Reply::factory()->count(3)->create([
            'threads_account_id' => $account->id,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListReplies::class)
            ->assertCanSeeTableRecords($replies);
    }
}
```

- [ ] **步驟 4：執行測試驗證通過**

執行：`php artisan test --compact tests/Feature/ReplyResourceTest.php`
預期：PASS

- [ ] **步驟 5：檢查點**

表單移除「留言者」、`post_id` 必填、建立走 Service 並觸發發佈。

---

## 任務 7：後台按鈕名詞、狀態顯示與 action 收斂

**檔案：**
- 修改：`app/Filament/Resources/Replies/Pages/ListReplies.php`
- 修改：`app/Filament/Resources/Replies/Tables/RepliesTable.php`

- [ ] **步驟 1：更新 `ListReplies` 的新增按鈕 label**

將 `app/Filament/Resources/Replies/Pages/ListReplies.php` 的 `getHeaderActions()` 改為：

```php
protected function getHeaderActions(): array
{
    return [
        CreateAction::make()->label('新增貼文回覆'),
    ];
}
```

- [ ] **步驟 2：更新 `RepliesTable`**

將 `app/Filament/Resources/Replies/Tables/RepliesTable.php` 改為：

1. 狀態欄位改用 `->badge()`（自動套用 `getLabel`／`getColor`）。
2. 「回覆」action 改名「回應回覆」，並改為呼叫 `ReplyService::publish()`。
3. 移除不再需要的 `ThreadsClient` 匯入。

```php
<?php

namespace App\Filament\Resources\Replies\Tables;

use App\Enums\ReplyStatus;
use App\Models\Reply;
use App\Services\ReplyService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RepliesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('threadsAccount.username')
                    ->label('來源帳號')
                    ->formatStateUsing(fn (?string $state): string => $state ? "@{$state}" : '-'),

                TextColumn::make('author_username')
                    ->label('留言者')
                    ->formatStateUsing(fn (?string $state): string => $state ? "@{$state}" : '-')
                    ->searchable(),

                TextColumn::make('text')
                    ->label('留言內容')
                    ->wrap()
                    ->limit(100),

                TextColumn::make('post.text')
                    ->label('所屬貼文')
                    ->limit(40)
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label('狀態')
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('threads_account_id')
                    ->label('帳號')
                    ->relationship('threadsAccount', 'username'),
                SelectFilter::make('status')
                    ->label('狀態')
                    ->options(ReplyStatus::class),
            ])
            ->recordActions([
                Action::make('reply')
                    ->label('回應回覆')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->visible(fn (Reply $record): bool => $record->status === ReplyStatus::New)
                    ->form([
                        Textarea::make('text')
                            ->label('回應內容')
                            ->required()
                            ->maxLength(500)
                            ->rows(3),
                    ])
                    ->action(function (Reply $record, array $data, ReplyService $replies): void {
                        try {
                            $replies->publish($record, $data['text']);

                            Notification::make()
                                ->title('已排程回應')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('回應失敗')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('ignore')
                    ->label('忽略')
                    ->icon('heroicon-o-no-symbol')
                    ->color('gray')
                    ->visible(fn (Reply $record): bool => $record->status === ReplyStatus::New)
                    ->requiresConfirmation()
                    ->action(function (Reply $record): void {
                        $record->update(['status' => ReplyStatus::Ignored]);

                        Notification::make()
                            ->title('已忽略')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
```

> **注意：** `TextColumn::make('author_username')` 的 `formatStateUsing` 改為 `?string` 型別，以相容 manual 記錄的空字串／null。

- [ ] **步驟 3：編寫／更新測試驗證**

在 `tests/Feature/ReplyResourceTest.php` 加入列表動作測試（驗證「回應回覆」action 呼叫 Service 而非直接發佈）：

```php
public function test_reply_action_dispatches_publish_job(): void
{
    Queue::fake();

    $account = ThreadsAccount::factory()->create();
    $reply = Reply::factory()->create([
        'threads_account_id' => $account->id,
        'threads_reply_id' => '12345',
        'status' => ReplyStatus::New,
    ]);

    Livewire::actingAs(User::factory()->create())
        ->test(ListReplies::class)
        ->callTableAction('reply', $reply, ['text' => '回應內容'])
        ->assertNotified();

    Queue::assertPushed(\App\Jobs\PublishReply::class, function ($job) use ($reply) {
        return $job->replyId === $reply->id && $job->replyText === '回應內容';
    });
}
```

- [ ] **步驟 4：執行測試驗證通過**

執行：`php artisan test --compact tests/Feature/ReplyResourceTest.php`
預期：PASS

- [ ] **步驟 5：檢查點**

按鈕名詞「新增貼文回覆」「回應回覆」就緒，狀態 badge 自動顯示，action 走 Service。

---

## 任務 8：回覆列表說明區加入延遲說明

**檔案：**
- 修改：`app/Filament/Resources/Replies/Widgets/RepliesSyncNotice.php`
- 修改：`resources/views/filament/widgets/replies-sync-notice.blade.php`

- [ ] **步驟 1：`RepliesSyncNotice` 傳遞延遲秒數**

將 `app/Filament/Resources/Replies/Widgets/RepliesSyncNotice.php` 的 `getViewData()` 改為：

```php
use App\Jobs\CollectThreadsReplies;
use App\Jobs\PublishScheduledPost;

// ...

protected function getViewData(): array
{
    return [
        'syncInterval' => CollectThreadsReplies::SYNC_INTERVAL_MINUTES,
        'publishDelaySeconds' => PublishScheduledPost::PUBLISH_DELAY_SECONDS,
    ];
}
```

- [ ] **步驟 2：更新 Blade view**

將 `resources/views/filament/widgets/replies-sync-notice.blade.php` 改為：

```blade
<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-start gap-3">
            <x-filament::icon
                icon="heroicon-o-information-circle"
                class="h-5 w-5 text-primary-500 shrink-0 mt-0.5"
            />

            <div class="space-y-1">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    回覆資料每 {{ $syncInterval }} 分鐘自動同步一次，新留言可能不會立即顯示。請稍候片刻後重新整理頁面。
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    回覆發佈採用兩階段機制，建立後約 {{ $publishDelaySeconds }} 秒才會顯示在 Threads 上。
                </p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
```

- [ ] **步驟 3：手動驗證**

執行：`php artisan test --compact tests/Feature/ReplyResourceTest.php`
預期：PASS（確保 view 變更未破壞列表頁）。

- [ ] **步驟 4：檢查點**

說明區顯示同步間隔 + 發佈延遲，秒數取自常數。

---

## 任務 9：MCP 工具對齊

**檔案：**
- 修改：`app/Mcp/Tools/CreateReplyTool.php`
- 修改：`app/Mcp/Tools/ListRepliesTool.php`

- [ ] **步驟 1：更新 `CreateReplyTool`**

將 `app/Mcp/Tools/CreateReplyTool.php` 改為（移除 `author_username`、`post_id` 必填、走 `createPostReply`）：

```php
<?php

namespace App\Mcp\Tools;

use App\Services\ReplyService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('建立一筆貼文回覆並發佈至 Threads，需指定帳號、目標貼文與回覆內容。')]
class CreateReplyTool extends Tool
{
    public function handle(Request $request, ReplyService $replies): Response|ResponseFactory
    {
        $data = $request->validate([
            'threads_account_id' => ['required', 'integer', 'exists:threads_accounts,id'],
            'post_id' => ['required', 'integer', 'exists:posts,id'],
            'text' => ['required', 'string', 'max:500'],
        ]);

        $reply = $replies->createPostReply(
            (int) $data['threads_account_id'],
            (int) $data['post_id'],
            $data['text'],
        );

        return Response::structured([
            'reply' => [
                'id' => $reply->id,
                'threads_account_id' => $reply->threads_account_id,
                'post_id' => $reply->post_id,
                'text' => $reply->text,
                'status' => $reply->status->value,
            ],
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'threads_account_id' => $schema->integer()
                ->description('來源 Threads 帳號 ID')
                ->required(),
            'post_id' => $schema->integer()
                ->description('目標貼文 ID')
                ->required(),
            'text' => $schema->string()
                ->description('回覆內容（最多 500 字元）')
                ->required(),
        ];
    }
}
```

- [ ] **步驟 2：更新 `ListRepliesTool`**

將 `app/Mcp/Tools/ListRepliesTool.php` 的 `map` 納入 `error_message`：

```php
$result = $replies->list($data)->map(fn ($reply): array => [
    'id' => $reply->id,
    'threads_account_id' => $reply->threads_account_id,
    'post_id' => $reply->post_id,
    'author_username' => $reply->author_username,
    'text' => $reply->text,
    'status' => $reply->status->value,
    'error_message' => $reply->error_message,
    'replied_at' => $reply->replied_at,
]);
```

- [ ] **步驟 3：更新 `McpToolsTest`**

將 `tests/Feature/McpToolsTest.php` 中 `test_create_reply_creates_manual_reply` 與 `test_create_reply_without_post_sets_null_post_id` 取代為：

```php
public function test_create_reply_requires_post(): void
{
    $account = ThreadsAccount::factory()->create();

    ThreadsMcpServer::tool(CreateReplyTool::class, [
        'threads_account_id' => $account->id,
        'text' => '缺少貼文的回覆',
    ])->assertHasErrors();
}

public function test_create_reply_creates_post_reply(): void
{
    Queue::fake();

    $account = ThreadsAccount::factory()->create();
    $post = Post::factory()->published()->create(['threads_account_id' => $account->id]);

    ThreadsMcpServer::tool(CreateReplyTool::class, [
        'threads_account_id' => $account->id,
        'post_id' => $post->id,
        'text' => '來自 MCP 的回覆',
    ])->assertOk();

    $this->assertDatabaseHas('replies', [
        'threads_account_id' => $account->id,
        'post_id' => $post->id,
        'text' => '來自 MCP 的回覆',
        'source' => 'manual',
        'status' => 'new',
    ]);

    Queue::assertPushed(\App\Jobs\PublishReply::class, 1);
}
```

> 注意：`McpToolsTest` 需補 `use Illuminate\Support\Facades\Queue;` 匯入。

- [ ] **步驟 4：執行測試驗證通過**

執行：`php artisan test --compact tests/Feature/McpToolsTest.php`
預期：PASS

- [ ] **步驟 5：檢查點**

MCP `create-reply` 參數與後台一致，走 Service 並觸發發佈。

---

## 任務 10：使用說明頁面同步

**檔案：**
- 檢查：`app/Filament/Pages/UsageGuide.php` 與 `resources/views/filament/pages/usage-guide.blade.php`

- [ ] **步驟 1：搜尋使用說明中的回覆名詞**

執行：`grep -rin "回覆\|reply\|回應" app/Filament/Pages/UsageGuide.php resources/views/filament/pages/usage-guide.blade.php`
預期：找出所有需同步的名詞。

- [ ] **步驟 2：同步名詞與流程**

若使用說明提及「新增回覆」「回覆」等名詞，改為「新增貼文回覆」「回應回覆」；若有描述回覆發佈流程，補上「兩階段發佈、約 30 秒」的說明，數值取自常數。

- [ ] **步驟 3：檢查點**

使用說明與實作同步。

---

## 任務 11：收尾驗證

- [ ] **步驟 1：執行完整測試**

執行：`php artisan test --compact`
預期：全部 PASS。

- [ ] **步驟 2：執行 Pint 格式化**

執行：`vendor/bin/pint --dirty --format agent`
預期：格式化通過，無 style 錯誤。

- [ ] **步驟 3：確認無既有測試被移除**

執行：`git diff --stat tests/`
預期：僅修改內容，無刪除測試檔案。

- [ ] **步驟 4：回報 commit 建議與變更清單**

整理「建議 commit 訊息」與「變更檔案清單」回報使用者，由使用者自行 commit。

---

## 自檢（實作前內嵌）

**1. 規格覆蓋度：**
- `reply-publishing` spec 的「新增貼文回覆並發佈」→ 任務 4、5 ✅
- 「回應回覆並發佈」→ 任務 4、5、7 ✅
- 「回覆發佈狀態追蹤」→ 任務 1、5 ✅
- 「後台與 MCP 共用發佈規則」→ 任務 4、6、7、9 ✅
- 「回覆列表顯示發佈延遲說明」→ 任務 8 ✅
- `reply-manual-create` spec 的表單欄位與按鈕名詞 → 任務 6、7 ✅
- `mcp-server` spec 的 `create-reply` 參數 → 任務 9 ✅

**2. 占位符掃描：** 無「待定」「TODO」「後續實現」等占位。

**3. 型別一致性：** `PublishReply` 的建構子參數 `replyId`、`creationId`、`replyText` 與 `ReplyService` 的 `dispatch` 呼叫一致；`createPostReply(int, int, string)` 簽章在 Service、CreateReply 頁面、MCP tool 三處一致。

# 多圖片發文（Carousel）實作計畫

> **面向 AI 代理的工作者：** 必需子技能：使用 superpowers:subagent-driven-development（推薦）或 superpowers:executing-plans 逐任務實現此計畫。步驟使用複選框（`- [ ]`）語法來跟踪進度。

**目標：** 將 Post 從單圖片擴充為支援最多 10 張圖片，透過 Threads Carousel API 發佈輪播貼文，後台提供 Repeater 多圖上傳與拖曳排序，MCP 支援 `image_urls` 陣列。

**架構：** 新建 `post_images` 關聯表取代 `posts.image_path` 欄位；`ThreadsClient` 新增 `createCarouselItemContainer` / `createCarouselContainer`；`PublishScheduledPost` 擴充為三階段（單圖 / Carousel 子圖 / Carousel 容器）共用錯誤處理；Filament 表單改用 `Repeater` 管理多圖，列表頁改為 Grid 卡片佈局。

**技術棧：** Laravel 13, Filament 5, SQLite, PHP 8.4, PHPUnit

---

## 檔案結構

| 檔案 | 操作 | 職責 |
|------|------|------|
| `database/migrations/xxxx_create_post_images_table.php` | 建立 | 建立 `post_images` 表、遷移資料、drop `image_path` |
| `app/Models/PostImage.php` | 建立 | `PostImage` Model |
| `app/Models/Post.php` | 修改 | 新增 `images()` 關聯，移除 `image_path`，刪除時 cascade 刪圖片 |
| `app/Services/ThreadsClient.php` | 修改 | 新增 `createCarouselItemContainer` / `createCarouselContainer` |
| `app/Services/PostService.php` | 修改 | `create()` 支援 `image_paths` / `image_urls` 陣列，建立 `PostImage` |
| `app/Jobs/PublishScheduledPost.php` | 修改 | 三階段流程：Stage1 判斷單/多圖，Stage2 Carousel 容器，Stage3 發佈 |
| `app/Filament/Resources/Posts/Schemas/PostForm.php` | 修改 | Repeater + FileUpload 取代單一 FileUpload |
| `app/Filament/Resources/Posts/Pages/ListPosts.php` | 修改 | Grid 卡片佈局取代 Table |
| `app/Filament/Resources/Posts/Pages/CreatePost.php` | 修改 | mutate 相容多圖關聯 |
| `app/Filament/Resources/Posts/Pages/EditPost.php` | 修改 | mutate 相容多圖關聯 |
| `app/Filament/Resources/Posts/Tables/PostsTable.php` | 刪除 | 改用 Grid 後不再需要 Table |
| `app/Filament/Resources/Posts/PostResource.php` | 修改 | list page 改用 Grid |
| `app/Mcp/Tools/CreatePostTool.php` | 修改 | `image_url` → `image_urls` |
| `tests/Unit/ThreadsClientTest.php` | 修改 | 新增 Carousel 方法測試 |
| `tests/Feature/PostServiceTest.php` | 修改 | 新增多圖建立、上限驗證、遷移測試 |
| `tests/Feature/PublishScheduledPostTest.php` | 修改 | 新增 Carousel 發佈成功/失敗測試 |
| `tests/Feature/McpToolsTest.php` | 修改 | `image_urls` 參數測試 |
| `tests/Feature/PostResourceTest.php` | 修改 | 多圖上傳與 Grid 測試 |
| `database/factories/PostFactory.php` | 修改 | 移除 `image_path` |

---

### 任務 1：建立 `post_images` Migration

**檔案：**
- 建立：`database/migrations/xxxx_xx_xx_xxxxxx_create_post_images_table.php`
- 修改：`app/Models/Post.php`
- 修改：`database/factories/PostFactory.php`

- [ ] **步驟 1：使用 Artisan 建立 migration**

```bash
php artisan make:migration create_post_images_table --no-interaction
```

- [ ] **步驟 2：撰寫 migration up()**

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. 建立 post_images 表
        Schema::create('post_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 2. 遷移既有 posts.image_path 資料
        DB::statement('
            INSERT INTO post_images (post_id, image_path, sort_order, created_at, updated_at)
            SELECT id, image_path, 0, created_at, updated_at
            FROM posts
            WHERE image_path IS NOT NULL
        ');

        // 3. Drop posts.image_path 欄位
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }

    public function down(): void
    {
        // 還原 image_path 欄位
        Schema::table('posts', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('text');
        });

        // 從 post_images 還原資料（取 sort_order=0 的首張圖）
        DB::statement('
            UPDATE posts SET image_path = (
                SELECT image_path FROM post_images
                WHERE post_images.post_id = posts.id
                ORDER BY sort_order ASC
                LIMIT 1
            )
        ');

        Schema::dropIfExists('post_images');
    }
};
```

- [ ] **步驟 3：執行 migration 驗證**

```bash
php artisan migrate --no-interaction
```

- [ ] **步驟 4：確認資料遷移正確（tinker）**

```bash
php artisan tinker --execute 'App\Models\Post::has("images")->count();'
```

- [ ] **步驟 5：更新 `Post.php` Model**

將 `$fillable` 中的 `'image_path'` 移除，新增 `images()` 關聯，更新 `booted()` 以刪除圖片檔案：

```php
// Post.php - 修改部分

// fillable 移除 'image_path'
protected $fillable = [
    'user_id',
    'threads_account_id',
    'threads_media_id',
    'text',
    'scheduled_at',
    'published_at',
    'status',
    'publish_attempts',
    'error_message',
];

// 新增關聯
public function images(): HasMany
{
    return $this->hasMany(PostImage::class)->orderBy('sort_order');
}

// 更新 booted 以 cascade 刪除圖片檔案
protected static function booted(): void
{
    static::deleting(function (Post $post) {
        // 刪除圖片檔案
        $post->images->each(function (PostImage $image) {
            $disk = str_starts_with($image->image_path, 'http')
                ? null
                : Storage::disk('public');
            if ($disk !== null && $disk->exists($image->image_path)) {
                $disk->delete($image->image_path);
            }
        });
        // 刪除關聯 Reply
        $post->replies->each(fn ($reply) => $reply->delete());
    });
}
```

需在檔案頂部新增：
```php
use Illuminate\Support\Facades\Storage;
```

- [ ] **步驟 6：更新 `PostFactory.php`** 移除 `image_path`

```php
// PostFactory.php - 移除 image_path 相關狀態
// 目前 definition() 中沒有 image_path，但需確認無相關 state 使用到
```

- [ ] **步驟 7：執行現有測試確保 migration 沒破壞任何東西**

```bash
php artisan test --compact --filter=PostServiceTest
```

---

### 任務 2：建立 `PostImage` Model

**檔案：**
- 建立：`app/Models/PostImage.php`

- [ ] **步驟 1：使用 Artisan 建立 Model**

```bash
php artisan make:model PostImage --no-interaction
```

- [ ] **步驟 2：撰寫 PostImage Model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostImage extends Model
{
    protected $fillable = [
        'post_id',
        'image_path',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
```

- [ ] **步驟 3：確認 autoload**

```bash
composer dump-autoload
```

---

### 任務 3：擴充 `ThreadsClient` — Carousel 方法

**檔案：**
- 修改：`app/Services/ThreadsClient.php`
- 修改：`tests/Unit/ThreadsClientTest.php`

- [ ] **步驟 1：在 `ThreadsClient` 新增 `createCarouselItemContainer` 方法**

在 `createImageContainer` 之後新增：

```php
/**
 * Create a carousel item media container for a post.
 *
 * @see https://developers.facebook.com/docs/threads/posts#carousel-posts
 */
public function createCarouselItemContainer(ThreadsAccount $account, string $imageUrl): string
{
    $params = [
        'media_type' => 'IMAGE',
        'image_url' => $imageUrl,
        'is_carousel_item' => 'true',
        'access_token' => $account->access_token,
    ];

    $data = $this->request('POST', "/{$account->threads_user_id}/threads", $params);

    return $data['id'];
}
```

- [ ] **步驟 2：在 `ThreadsClient` 新增 `createCarouselContainer` 方法**

```php
/**
 * Create a carousel container that wraps carousel item containers.
 *
 * @param  string[]  $childrenIds
 * @see https://developers.facebook.com/docs/threads/posts#carousel-posts
 */
public function createCarouselContainer(ThreadsAccount $account, array $childrenIds, ?string $text = null): string
{
    $params = [
        'media_type' => 'CAROUSEL',
        'children' => implode(',', $childrenIds),
        'access_token' => $account->access_token,
    ];

    if ($text !== null && $text !== '') {
        $params['text'] = $text;
    }

    $data = $this->request('POST', "/{$account->threads_user_id}/threads", $params);

    return $data['id'];
}
```

- [ ] **步驟 3：新增測試 — Carousel Item Container**

在 `tests/Unit/ThreadsClientTest.php` 新增：

```php
public function test_create_carousel_item_container_returns_creation_id(): void
{
    $account = ThreadsAccount::factory()->create();

    $this->http->shouldReceive('request')
        ->once()
        ->with('POST', 'https://graph.threads.net/v1.0/'.$account->threads_user_id.'/threads', Mockery::on(function ($options) {
            return isset($options['form_params']['is_carousel_item'])
                && $options['form_params']['is_carousel_item'] === 'true'
                && $options['form_params']['media_type'] === 'IMAGE';
        }))
        ->andReturn(new Response(200, [], json_encode(['id' => 'carousel-item-id'])));

    $creationId = $this->client->createCarouselItemContainer($account, 'https://example.com/image.jpg');

    $this->assertSame('carousel-item-id', $creationId);
}
```

- [ ] **步驟 4：新增測試 — Carousel Container**

```php
public function test_create_carousel_container_returns_creation_id(): void
{
    $account = ThreadsAccount::factory()->create();

    $this->http->shouldReceive('request')
        ->once()
        ->with('POST', 'https://graph.threads.net/v1.0/'.$account->threads_user_id.'/threads', Mockery::on(function ($options) {
            return $options['form_params']['media_type'] === 'CAROUSEL'
                && $options['form_params']['children'] === 'id1,id2,id3';
        }))
        ->andReturn(new Response(200, [], json_encode(['id' => 'carousel-container-id'])));

    $creationId = $this->client->createCarouselContainer($account, ['id1', 'id2', 'id3'], 'caption text');

    $this->assertSame('carousel-container-id', $creationId);
}
```

- [ ] **步驟 5：執行 ThreadsClient 測試**

```bash
php artisan test --compact --filter=ThreadsClientTest
```

預期：所有測試通過（含新增的兩個）。

---

### 任務 4：更新 `PostService::create()` 支援多圖

**檔案：**
- 修改：`app/Services/PostService.php`
- 修改：`tests/Feature/PostServiceTest.php`

- [ ] **步驟 1：修改 `create()` 方法**

```php
/**
 * 建立一筆排程貼文。
 *
 * @param  array{threads_account_id: int, text?: string, image_paths?: string[], image_urls?: string[], scheduled_at: string}  $data
 */
public function create(array $data): Post
{
    $userId = auth()->id();

    $account = ThreadsAccount::query()
        ->where('user_id', $userId)
        ->find($data['threads_account_id']);

    if ($account === null) {
        throw new InvalidArgumentException('帳號不存在或無權存取');
    }

    // 收集圖片路徑：統一轉為陣列
    $imagePaths = [];

    if (! empty($data['image_paths'])) {
        $imagePaths = $data['image_paths'];
    } elseif (! empty($data['image_urls'])) {
        $imagePaths = $data['image_urls'];
    }

    // 驗證圖片數量上限
    if (count($imagePaths) > 10) {
        throw new InvalidArgumentException('圖片數量上限為 10 張');
    }

    // 驗證至少要有 text 或 image
    if (empty($data['text']) && empty($imagePaths)) {
        throw new InvalidArgumentException('貼文內容或圖片至少需填寫一項');
    }

    $post = new Post;
    $post->user_id = $userId;
    $post->threads_account_id = $data['threads_account_id'];
    $post->text = $data['text'] ?? null;
    $post->scheduled_at = $data['scheduled_at'];
    $post->status = PostStatus::Scheduled;
    $post->save();

    // 儲存圖片記錄
    foreach ($imagePaths as $index => $path) {
        $post->images()->create([
            'image_path' => $path,
            'sort_order' => $index,
        ]);
    }

    return $post->load('images');
}
```

- [ ] **步驟 2：更新 `list()` 方法預載 images**

```php
public function list(array $filters = [], ?int $userId = null): Collection
{
    $userId ??= auth()->id();

    $query = Post::query()->with(['threadsAccount', 'images'])->where('user_id', $userId);

    // ...其餘不變
}
```

- [ ] **步驟 3：更新 `find()` 預載 images**

```php
public function find(int $id, ?int $userId = null): ?Post
{
    $userId ??= auth()->id();

    return Post::query()->with(['threadsAccount', 'images'])->where('user_id', $userId)->find($id);
}
```

- [ ] **步驟 4：更新測試 `PostServiceTest`** 新增多圖測試

```php
public function test_create_with_multiple_images(): void
{
    $user = User::factory()->create();
    $this->actingAs($user);

    $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);

    $post = app(PostService::class)->create([
        'threads_account_id' => $account->id,
        'text' => '多圖測試',
        'image_paths' => ['posts/img1.jpg', 'posts/img2.jpg', 'posts/img3.jpg'],
        'scheduled_at' => now()->addHour(),
    ]);

    $this->assertSame(PostStatus::Scheduled, $post->status);
    $this->assertCount(3, $post->images);
    $this->assertSame('posts/img1.jpg', $post->images->first()->image_path);
    $this->assertSame(0, $post->images->first()->sort_order);
    $this->assertSame(2, $post->images->last()->sort_order);
}

public function test_create_rejects_more_than_10_images(): void
{
    $user = User::factory()->create();
    $this->actingAs($user);

    $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('圖片數量上限為 10 張');

    app(PostService::class)->create([
        'threads_account_id' => $account->id,
        'text' => '超過上限',
        'image_paths' => array_fill(0, 11, 'posts/img.jpg'),
        'scheduled_at' => now()->addHour(),
    ]);
}

public function test_create_with_image_urls(): void
{
    $user = User::factory()->create();
    $this->actingAs($user);

    $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);

    $post = app(PostService::class)->create([
        'threads_account_id' => $account->id,
        'text' => 'MCP 多圖',
        'image_urls' => ['https://example.com/1.jpg', 'https://example.com/2.jpg'],
        'scheduled_at' => now()->addHour(),
    ]);

    $this->assertCount(2, $post->images);
    $this->assertSame('https://example.com/1.jpg', $post->images->first()->image_path);
}

public function test_create_with_text_only_still_works(): void
{
    $user = User::factory()->create();
    $this->actingAs($user);

    $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);

    $post = app(PostService::class)->create([
        'threads_account_id' => $account->id,
        'text' => '純文字貼文',
        'scheduled_at' => now()->addHour(),
    ]);

    $this->assertSame(PostStatus::Scheduled, $post->status);
    $this->assertCount(0, $post->images);
}
```

- [ ] **步驟 5：執行 PostService 測試**

```bash
php artisan test --compact --filter=PostServiceTest
```

預期：全部通過。

---

### 任務 5：擴充 `PublishScheduledPost` Job — 三階段流程

**檔案：**
- 修改：`app/Jobs/PublishScheduledPost.php`
- 修改：`tests/Feature/PublishScheduledPostTest.php`

- [ ] **步驟 1：新增 `$childIds` 參數**

```php
public function __construct(
    private readonly int $postId,
    private readonly ?string $creationId = null,
    private readonly ?array $childIds = null,
) {}
```

- [ ] **步驟 2：改寫 `handle()` 支援三階段**

```php
public function handle(ThreadsClient $threads): void
{
    $post = Post::query()->with('images')->find($this->postId);

    // 判斷當前階段
    $isStage1 = $this->creationId === null && $this->childIds === null;
    $isStage2 = $this->creationId === null && $this->childIds !== null;
    $isStage3 = $this->creationId !== null;

    $expectedStatus = $isStage1 ? PostStatus::Scheduled : PostStatus::Publishing;

    if ($post === null || $post->status !== $expectedStatus) {
        return;
    }

    $account = $post->threadsAccount;

    if ($account === null) {
        return;
    }

    // 停用的使用者：排程貼文不發佈
    if ($post->user !== null && ! $post->user->is_active) {
        return;
    }

    try {
        // --- Stage 1: 建立 container(s) ---
        if ($isStage1) {
            $imageCount = $post->images->count();

            if ($imageCount === 0) {
                // 純文字
                $creationId = $threads->createTextContainer($account, $post->text);
                $post->update(['status' => PostStatus::Publishing]);

                static::dispatch($this->postId, $creationId)
                    ->delay(now()->addSeconds(self::PUBLISH_DELAY_SECONDS));

            } elseif ($imageCount === 1) {
                // 單圖（沿用既有流程）
                $imageUrl = $this->resolveImageUrl($post->images->first()->image_path);
                $creationId = $threads->createImageContainer($account, $imageUrl, $post->text);
                $post->update(['status' => PostStatus::Publishing]);

                static::dispatch($this->postId, $creationId)
                    ->delay(now()->addSeconds(self::PUBLISH_DELAY_SECONDS));

            } else {
                // 多圖 Carousel: 為每張圖建立 is_carousel_item container
                $childIds = [];
                foreach ($post->images as $image) {
                    $imageUrl = $this->resolveImageUrl($image->image_path);
                    $childIds[] = $threads->createCarouselItemContainer($account, $imageUrl);
                }
                $post->update(['status' => PostStatus::Publishing]);

                static::dispatch($this->postId, null, $childIds)
                    ->delay(now()->addSeconds(self::PUBLISH_DELAY_SECONDS));
            }

            return;
        }

        // --- Stage 2: 建立 Carousel container ---
        if ($isStage2) {
            $creationId = $threads->createCarouselContainer($account, $this->childIds, $post->text);

            static::dispatch($this->postId, $creationId)
                ->delay(now()->addSeconds(self::PUBLISH_DELAY_SECONDS));

            return;
        }

        // --- Stage 3: 發佈 ---
        if ($isStage3) {
            $mediaId = $threads->publishContainer($account, $this->creationId);

            $post->update([
                'status' => PostStatus::Published,
                'threads_media_id' => $mediaId,
                'published_at' => now(),
                'error_message' => null,
            ]);
        }

    } catch (ThreadsApiException $e) {
        if ($e->isTokenInvalid()) {
            $account->update(['status' => ThreadsAccountStatus::NeedsReauth]);
            $post->update([
                'status' => PostStatus::Failed,
                'error_message' => 'token 失效',
            ]);
        } elseif ($e->isRateLimited()) {
            $post->update([
                'status' => PostStatus::Failed,
                'error_message' => '已達每日發文上限',
            ]);
        } elseif ($e->isRetryable() && $post->publish_attempts < self::MAX_PUBLISH_ATTEMPTS) {
            $attempt = $post->publish_attempts + 1;
            $post->update(['publish_attempts' => $attempt]);

            static::dispatch($this->postId, $this->creationId, $this->childIds)
                ->delay(now()->addSeconds($attempt * self::RETRY_BACKOFF_SECONDS));
        } else {
            $post->update([
                'status' => PostStatus::Failed,
                'error_message' => $e->getMessage(),
            ]);
        }
    } catch (\Throwable $e) {
        $post->update([
            'status' => PostStatus::Failed,
            'error_message' => $e->getMessage(),
        ]);

        Log::error('Threads post publish failed', [
            'post_id' => $post->id,
            'error' => $e->getMessage(),
        ]);
    }
}

/**
 * 將 image_path 轉換為完整公開 URL。
 */
private function resolveImageUrl(string $imagePath): string
{
    if (str_starts_with($imagePath, 'http')) {
        return $imagePath;
    }

    return Storage::disk('public')->url($imagePath);
}
```

- [ ] **步驟 3：更新現有測試** — `test_first_stage_creates_container_and_sets_status_to_publishing`

```php
// PostFactory 已無 image_path，使用純文字測試保持不變，但需確保 Post 建立後無 images
public function test_first_stage_creates_container_and_sets_status_to_publishing(): void
{
    Queue::fake();

    $account = ThreadsAccount::factory()->create();
    $post = Post::factory()->create([
        'threads_account_id' => $account->id,
        'status' => PostStatus::Scheduled,
        'scheduled_at' => now()->subMinute(),
    ]);
    // 沒有 images → 純文字流程

    $threads = Mockery::mock(ThreadsClient::class);
    $threads->shouldReceive('createTextContainer')
        ->once()
        ->andReturn('creation-id-123');
    $threads->shouldReceive('publishContainer')->never();

    $job = new PublishScheduledPost($post->id);
    $job->handle($threads);

    $post->refresh();

    $this->assertSame(PostStatus::Publishing, $post->status);
    Queue::assertPushed(PublishScheduledPost::class, 1);
}
```

其他現有測試（published、token invalid、rate limit）保持不變，因為它們是 Stage 3 測試，不涉及圖片邏輯。

- [ ] **步驟 4：新增 Carousel 發佈測試**

```php
public function test_carousel_first_stage_creates_item_containers(): void
{
    Queue::fake();

    $account = ThreadsAccount::factory()->create();
    $post = Post::factory()->create([
        'threads_account_id' => $account->id,
        'status' => PostStatus::Scheduled,
        'scheduled_at' => now()->subMinute(),
    ]);
    // 手動建立 3 張圖片
    $post->images()->createMany([
        ['image_path' => 'posts/img1.jpg', 'sort_order' => 0],
        ['image_path' => 'posts/img2.jpg', 'sort_order' => 1],
        ['image_path' => 'posts/img3.jpg', 'sort_order' => 2],
    ]);

    $threads = Mockery::mock(ThreadsClient::class);
    $threads->shouldReceive('createCarouselItemContainer')
        ->times(3)
        ->andReturn('item-1', 'item-2', 'item-3');
    $threads->shouldReceive('createCarouselContainer')->never();
    $threads->shouldReceive('publishContainer')->never();

    $job = new PublishScheduledPost($post->id);
    $job->handle($threads);

    $post->refresh();
    $this->assertSame(PostStatus::Publishing, $post->status);
    Queue::assertPushed(PublishScheduledPost::class, function ($job) {
        return $job->childIds === ['item-1', 'item-2', 'item-3'];
    });
}

public function test_carousel_second_stage_creates_carousel_container(): void
{
    Queue::fake();

    $account = ThreadsAccount::factory()->create();
    $post = Post::factory()->create([
        'threads_account_id' => $account->id,
        'status' => PostStatus::Publishing,
        'scheduled_at' => now()->subMinute(),
    ]);

    $threads = Mockery::mock(ThreadsClient::class);
    $threads->shouldReceive('createCarouselContainer')
        ->once()
        ->with($account, ['item-1', 'item-2'], $post->text)
        ->andReturn('carousel-container-id');

    $job = new PublishScheduledPost($post->id, null, ['item-1', 'item-2']);
    $job->handle($threads);

    Queue::assertPushed(PublishScheduledPost::class, function ($job) {
        return $job->creationId === 'carousel-container-id';
    });
}

public function test_carousel_third_stage_publishes(): void
{
    $account = ThreadsAccount::factory()->create();
    $post = Post::factory()->create([
        'threads_account_id' => $account->id,
        'status' => PostStatus::Publishing,
        'scheduled_at' => now()->subMinute(),
    ]);

    $threads = Mockery::mock(ThreadsClient::class);
    $threads->shouldReceive('publishContainer')
        ->once()
        ->andReturn('media-id-456');

    $job = new PublishScheduledPost($post->id, 'carousel-container-id');
    $job->handle($threads);

    $post->refresh();
    $this->assertSame(PostStatus::Published, $post->status);
    $this->assertSame('media-id-456', $post->threads_media_id);
}

public function test_single_image_still_uses_create_image_container(): void
{
    Queue::fake();

    $account = ThreadsAccount::factory()->create();
    $post = Post::factory()->create([
        'threads_account_id' => $account->id,
        'status' => PostStatus::Scheduled,
        'scheduled_at' => now()->subMinute(),
    ]);
    $post->images()->create(['image_path' => 'posts/single.jpg', 'sort_order' => 0]);

    $threads = Mockery::mock(ThreadsClient::class);
    $threads->shouldReceive('createImageContainer')
        ->once()
        ->andReturn('single-container-id');
    $threads->shouldReceive('createCarouselItemContainer')->never();

    $job = new PublishScheduledPost($post->id);
    $job->handle($threads);

    $post->refresh();
    $this->assertSame(PostStatus::Publishing, $post->status);
}
```

- [ ] **步驟 5：執行 PublishScheduledPost 測試**

```bash
php artisan test --compact --filter=PublishScheduledPostTest
```

預期：全部通過。

---

### 任務 6：更新 Filament PostForm — Repeater + FileUpload

**檔案：**
- 修改：`app/Filament/Resources/Posts/Schemas/PostForm.php`
- 修改：`app/Filament/Resources/Posts/Pages/CreatePost.php`

- [ ] **步驟 1：改寫 PostForm — 將 FileUpload 改為 Repeater**

```php
<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\PostStatus;
use App\Enums\ThreadsAccountStatus;
use App\Models\ThreadsAccount;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Operation;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('貼文狀態資訊')
                    ->schema([
                        TextEntry::make('status')
                            ->label('狀態')
                            ->badge(),
                        TextEntry::make('published_at')
                            ->label('發佈時間')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('-'),
                        TextEntry::make('error_message')
                            ->label('錯誤訊息')
                            ->placeholder('-'),
                    ])
                    ->columns(3)
                    ->hiddenOn(Operation::Create),

                Hidden::make('status'),

                Select::make('threads_account_id')
                    ->label('目標帳號')
                    ->relationship('threadsAccount', 'username', fn ($query) => $query->where('user_id', auth()->id()))
                    ->getOptionLabelFromRecordUsing(fn ($record) => "@{$record->username}")
                    ->required()
                    ->disabled(fn ($get, string $operation): bool => $operation === 'edit' && ! in_array($get('status'), [PostStatus::Draft->value, PostStatus::Scheduled->value]))
                    ->helperText(fn ($get) => self::getAccountWarning($get('threads_account_id'))),

                Repeater::make('images')
                    ->label('圖片')
                    ->relationship()
                    ->schema([
                        FileUpload::make('image_path')
                            ->label('圖片檔案')
                            ->image()
                            ->disk('public')
                            ->directory('posts')
                            ->acceptedFileTypes(['image/jpeg', 'image/png'])
                            ->maxSize(8192)
                            ->required(),
                    ])
                    ->orderColumn('sort_order')
                    ->reorderable()
                    ->maxItems(10)
                    ->addActionLabel('新增圖片')
                    ->columns(1)
                    ->disabled(fn ($get, string $operation): bool => $operation === 'edit' && ! in_array($get('status'), [PostStatus::Draft->value, PostStatus::Scheduled->value]))
                    ->helperText('支援 JPEG、PNG，最大 8MB，最多 10 張。文字與圖片至少需填寫一項。'),

                Textarea::make('text')
                    ->label('貼文內容')
                    ->nullable()
                    ->maxLength(500)
                    ->rows(4)
                    ->disabled(fn ($get, string $operation): bool => $operation === 'edit' && ! in_array($get('status'), [PostStatus::Draft->value, PostStatus::Scheduled->value]))
                    ->helperText('最多 500 字元。文字與圖片至少需填寫一項。'),

                DateTimePicker::make('scheduled_at')
                    ->label('排程時間')
                    ->required()
                    ->default(now())
                    ->native(false)
                    ->disabled(fn ($get, string $operation): bool => $operation === 'edit' && ! in_array($get('status'), [PostStatus::Draft->value, PostStatus::Scheduled->value])),
            ]);
    }

    private static function getAccountWarning(mixed $accountId): ?string
    {
        if ($accountId === null) {
            return null;
        }

        $account = ThreadsAccount::query()->find($accountId);

        if ($account?->status === ThreadsAccountStatus::NeedsReauth) {
            return '⚠️ 此帳號需要重新授權，發佈時可能失敗';
        }

        return null;
    }
}
```

- [ ] **步驟 2：更新 `CreatePost::mutateFormDataBeforeCreate`**

```php
protected function mutateFormDataBeforeCreate(array $data): array
{
    if (! empty($data['scheduled_at'])) {
        $data['status'] = PostStatus::Scheduled->value;
    }

    $data['user_id'] = auth()->id();

    return $data;
}
// Repeater 的 relationship() 會自動處理 images 關聯的建立，
// 不需要手動在 mutate 中處理 image_path
```

- [ ] **步驟 3：更新 `EditPost::mutateFormDataBeforeSave`**（不需變更，Repeater 自動處理）

`mutateFormDataBeforeSave` 無需修改 — Repeater with `->relationship()` 會自動同步 `post_images`。

---

### 任務 7：更新 Filament ListPosts — Grid 卡片佈局

**檔案：**
- 修改：`app/Filament/Resources/Posts/Pages/ListPosts.php`
- 修改：`app/Filament/Resources/Posts/PostResource.php`
- 刪除：`app/Filament/Resources/Posts/Tables/PostsTable.php`

- [ ] **步驟 1：改寫 `ListPosts` 為 Grid 佈局**

```php
<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use Filament\Actions\CreateAction;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
```

等等 — Filament 的 Grid 卡片佈局可以用 `table()` 的 `contentGrid()` 來實現，不需要完全放棄 Table。這樣可以保留搜尋、篩選功能。

```php
<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use App\Services\PostService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->columns([
                // 使用 Stack 佈局在卡片內垂直排列
                \Filament\Tables\Columns\Layout\Stack::make([
                    ImageColumn::make('firstImage')
                        ->label('')
                        ->state(fn (Post $record) => $record->images->first()?->image_path)
                        ->disk('public')
                        ->height(200)
                        ->extraImgAttributes(fn (Post $record) => [
                            'class' => 'w-full object-cover rounded-t-lg',
                        ]),

                    \Filament\Tables\Columns\Layout\Stack::make([
                        TextColumn::make('threadsAccount.username')
                            ->label('帳號')
                            ->formatStateUsing(fn (?string $state): string => $state ? "@{$state}" : '-'),

                        TextColumn::make('status')
                            ->label('狀態')
                            ->badge(),

                        TextColumn::make('text')
                            ->label('內容')
                            ->limit(50),

                        TextColumn::make('scheduled_at')
                            ->label('排程')
                            ->dateTime('m-d H:i'),
                    ])->space(1),
                ])->space(2),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn ($record) => in_array($record->status, [\App\Enums\PostStatus::Draft, \App\Enums\PostStatus::Scheduled])),
                DeleteAction::make()
                    ->visible(fn ($record) => ! in_array($record->status, [\App\Enums\PostStatus::Deleting]))
                    ->action(function ($record) {
                        app(PostService::class)->delete($record->id);
                    }),
            ]);
    }
}
```

Hmm，這樣太複雜。讓我簡化 — 使用 `contentGrid` 配合 Stack 是最乾淨的做法。

實際上 Filament v4 的 Grid 卡片模式建議使用 `contentGrid` + `Stack` 欄位佈局。我來寫正確的實作。

- [ ] **步驟 1（修正）：改寫 `ListPosts`**

```php
<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use App\Services\PostService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Table;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->columns([
                Stack::make([
                    ImageColumn::make('images.image_path')
                        ->label('')
                        ->disk('public')
                        ->height(200)
                        ->placeholder('無圖片'),

                    Stack::make([
                        TextColumn::make('threadsAccount.username')
                            ->formatStateUsing(fn (?string $state): string => $state ? "@{$state}" : '-'),

                        TextColumn::make('status')
                            ->badge(),

                        TextColumn::make('text')
                            ->limit(50),

                        TextColumn::make('scheduled_at')
                            ->dateTime('m-d H:i'),
                    ])->space(1),
                ])->space(2),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (Post $record) => in_array($record->status, [PostStatus::Draft, PostStatus::Scheduled])),
                DeleteAction::make()
                    ->visible(fn (Post $record) => ! in_array($record->status, [PostStatus::Deleting]))
                    ->action(function (Post $record) {
                        app(PostService::class)->delete($record->id);
                    }),
            ]);
    }
}
```

- [ ] **步驟 2：更新 `PostResource` — 移除 `PostsTable::configure`，改用內聯 table()**

如果 ListPosts 直接覆寫 `table()`，則 PostResource 中不再需要 `table()` 方法（或保留空實作）。

```php
// PostResource.php
// table() 方法可刪除或保留為空，因為 ListPosts 直接覆寫了
```

- [ ] **步驟 3：刪除 `PostsTable.php`**（不再需要）

```bash
rm app/Filament/Resources/Posts/Tables/PostsTable.php
```

- [ ] **步驟 4：更新 `PostResourceTest` — 改為 Grid 測試**

```php
// test_list_posts_shows_records - 需改為 Grid 相關 assertion
public function test_list_posts_shows_records(): void
{
    $user = User::factory()->create();
    $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);
    $posts = Post::factory()->count(3)->create([
        'threads_account_id' => $account->id,
        'user_id' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(ListPosts::class)
        ->assertOk()
        ->assertSee($posts->first()->text);
}
```

---

### 任務 8：更新 MCP `CreatePostTool`

**檔案：**
- 修改：`app/Mcp/Tools/CreatePostTool.php`
- 修改：`tests/Feature/McpToolsTest.php`

- [ ] **步驟 1：改寫 `CreatePostTool`**

```php
<?php

namespace App\Mcp\Tools;

use App\Services\PostService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('建立一筆排程貼文，需指定帳號、內容（或圖片 URL 陣列）與排程時間。')]
class CreatePostTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, PostService $posts): Response|ResponseFactory
    {
        $data = $request->validate([
            'threads_account_id' => ['required', 'integer', Rule::exists('threads_accounts', 'id')->where('user_id', auth()->id())],
            'text' => ['nullable', 'string', 'max:500'],
            'image_urls' => ['nullable', 'array', 'max:10'],
            'image_urls.*' => ['string', 'url'],
            'scheduled_at' => ['required', 'date'],
        ]);

        $imageUrls = $data['image_urls'] ?? [];

        // 驗證至少要有 text 或 image_urls
        if (empty($data['text']) && empty($imageUrls)) {
            return Response::error('貼文內容或圖片 URL 至少需填寫一項');
        }

        if (count($imageUrls) > 10) {
            return Response::error('圖片數量上限為 10 張');
        }

        $post = $posts->create([
            'threads_account_id' => $data['threads_account_id'],
            'text' => $data['text'] ?? null,
            'image_urls' => $imageUrls,
            'scheduled_at' => $data['scheduled_at'],
        ]);

        return Response::structured([
            'post' => [
                'id' => $post->id,
                'threads_account_id' => $post->threads_account_id,
                'text' => $post->text,
                'images' => $post->images->map(fn ($img) => [
                    'image_path' => $img->image_path,
                    'sort_order' => $img->sort_order,
                ])->toArray(),
                'scheduled_at' => $post->scheduled_at,
                'status' => $post->status->value,
            ],
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'threads_account_id' => $schema->integer()
                ->description('目標 Threads 帳號 ID')
                ->required(),
            'text' => $schema->string()
                ->description('貼文內容（最多 500 字元，與圖片至少需填寫一項）'),
            'image_urls' => $schema->array()
                ->items($schema->string()->format('uri'))
                ->description('圖片公開 URL 陣列（選填，最多 10 個。客戶端需自行上傳圖片到公開 URL）'),
            'scheduled_at' => $schema->string()
                ->description('排程時間（ISO 8601 或 Y-m-d H:i:s）')
                ->required(),
        ];
    }
}
```

- [ ] **步驟 2：更新 MCP 測試**

```php
public function test_create_post_creates_scheduled_post(): void
{
    $user = User::factory()->create();
    $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);

    ThreadsMcpServer::actingAs($user)
        ->tool(CreatePostTool::class, [
            'threads_account_id' => $account->id,
            'text' => '來自 MCP 的貼文',
            'scheduled_at' => now()->addHour()->toIso8601String(),
        ])->assertOk();

    $this->assertDatabaseHas('posts', [
        'threads_account_id' => $account->id,
        'text' => '來自 MCP 的貼文',
    ]);
}

public function test_create_post_with_multiple_image_urls(): void
{
    $user = User::factory()->create();
    $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);

    ThreadsMcpServer::actingAs($user)
        ->tool(CreatePostTool::class, [
            'threads_account_id' => $account->id,
            'text' => '多圖貼文',
            'image_urls' => [
                'https://example.com/img1.jpg',
                'https://example.com/img2.jpg',
                'https://example.com/img3.jpg',
            ],
            'scheduled_at' => now()->addHour()->toIso8601String(),
        ])->assertOk();

    $this->assertDatabaseHas('posts', ['text' => '多圖貼文']);
    $this->assertDatabaseCount('post_images', 3);
}

public function test_create_post_rejects_too_many_image_urls(): void
{
    $user = User::factory()->create();
    $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);

    ThreadsMcpServer::actingAs($user)
        ->tool(CreatePostTool::class, [
            'threads_account_id' => $account->id,
            'text' => '超過上限',
            'image_urls' => array_fill(0, 11, 'https://example.com/img.jpg'),
            'scheduled_at' => now()->addHour()->toIso8601String(),
        ])->assertHasErrors();
}
```

- [ ] **步驟 3：執行 MCP 測試**

```bash
php artisan test --compact --filter=McpToolsTest
```

---

### 任務 9：收尾 — 測試、格式化、使用說明

**檔案：**
- 修改：`resources/views/filament/pages/usage-guide.blade.php`（如存在）

- [ ] **步驟 1：執行全部測試**

```bash
php artisan test --compact
```

- [ ] **步驟 2：修正任何失敗的測試**

逐一檢查失敗原因並修正。

- [ ] **步驟 3：執行程式碼格式化**

```bash
vendor/bin/pint --format agent
```

- [ ] **步驟 4：更新使用說明頁面**

在使用說明中加入多圖發文的說明：支援最多 10 張圖片，後台可拖曳排序，發佈時自動以輪播方式呈現。

- [ ] **步驟 5：Commit**

```bash
git add -A
git commit -m "feat: 支援多圖片發文（Carousel），最多 10 張，後台 Grid 卡片佈局"
```

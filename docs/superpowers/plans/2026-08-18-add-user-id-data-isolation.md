# 使用者資料隔離（user_id）實作計畫

> **面向 AI 代理的工作者：** 必需子技能：使用 superpowers:subagent-driven-development（推薦）或 superpowers:executing-plans 逐任務實作此計畫。步驟使用複選框（`- [ ]`）語法來追蹤進度。

**目標：** 讓 `threads_accounts`、`posts`、`replies`、`threads_oauth_states` 四張表加入 `user_id`，並在後台（Filament）與 MCP 工具全面以 `user_id` 隔離資料，每位使用者僅能存取自己的資料。

**架構：** 四張業務表各加 `unsignedBigInteger` 的 `user_id` 欄位（不加 FK，僅 index）；寫入時取自 `auth()->id()`；查詢時在 Filament `getEloquentQuery()` 與 Service 層方法中以 `user_id` 顯式 scope。現有資料全數歸屬 `user_id = 2`。

**技術棧：** Laravel 13、PHP 8.4、Filament 4、`laravel/mcp`、Passport、PHPUnit、SQLite。

---

## 重要說明（實作前必讀）

### 測試執行命令

依專案慣例（`/memories/repo/testing.md`）：
- 單一測試：`php artisan test --compact --filter=<TestName>`
- 完整套件（記憶體不足時）：`php -d memory_limit=-1 vendor/bin/phpunit --no-coverage`

### Commit 策略

依使用者偏好，**本計畫不在任務中自動 commit**。每個任務完成後，由實作者提供「建議的 commit 訊息」與「變更檔案清單」，交由使用者自行 commit。

### 程式碼風格

所有 PHP 修改完成後，執行 `vendor/bin/pint --dirty --format agent` 修正格式。

### 測試隔離總規則（適用所有任務）

本變更的核心是「資料隔離」，因此**所有既有測試都必須更新**，讓資料的 `user_id` 與登入使用者一致，否則會被 scope 過濾掉而失敗。規則如下：

1. 每個測試建立一個 `$user = User::factory()->create()`，並以該 user 登入：
   - Filament：`Livewire::actingAs($user)->test(...)`
   - Service：`$this->actingAs($user)`
   - MCP：`ThreadsMcpServer::actingAs($user)->tool(...)`
2. 建立 `ThreadsAccount` / `Post` / `Reply` 時，明確傳入 `user_id => $user->id`（或讓其帳號 `user_id` 與 `$user` 一致）。

---

## 任務 1：Migrations 與 Models 與 Factories

**檔案：**
- 建立：`database/migrations/2026_08_18_100001_add_user_id_to_threads_accounts_table.php`
- 建立：`database/migrations/2026_08_18_100002_add_user_id_to_posts_table.php`
- 建立：`database/migrations/2026_08_18_100003_add_user_id_to_replies_table.php`
- 建立：`database/migrations/2026_08_18_100004_add_user_id_to_threads_oauth_states_table.php`
- 建立：`database/migrations/2026_08_18_100005_backfill_user_id_on_business_tables.php`
- 修改：`app/Models/ThreadsAccount.php`
- 修改：`app/Models/Post.php`
- 修改：`app/Models/Reply.php`
- 修改：`app/Models/OAuthState.php`
- 修改：`database/factories/ThreadsAccountFactory.php`
- 修改：`database/factories/PostFactory.php`
- 修改：`database/factories/ReplyFactory.php`

- [ ] **步驟 1.1：建立 4 個加欄位 migration**

`2026_08_18_100001_add_user_id_to_threads_accounts_table.php`：

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('threads_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->index()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('threads_accounts', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
```

其餘三份（`posts`、`replies`、`threads_oauth_states`）結構完全相同，僅 `Schema::table()` 的表名不同。欄位一律 `unsignedBigInteger('user_id')->nullable()->index()->after('id')`。

- [ ] **步驟 1.2：建立 backfill migration**

`2026_08_18_100005_backfill_user_id_on_business_tables.php`：

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('threads_accounts')->whereNull('user_id')->update(['user_id' => 2]);
        DB::table('posts')->whereNull('user_id')->update(['user_id' => 2]);
        DB::table('replies')->whereNull('user_id')->update(['user_id' => 2]);
        DB::table('threads_oauth_states')->whereNull('user_id')->update(['user_id' => 2]);
    }

    public function down(): void
    {
        // 資料已改寫，無法安全還原；保留為空操作。
    }
};
```

- [ ] **步驟 1.3：更新 4 個 Model**

`ThreadsAccount`、`Post`、`Reply`、`OAuthState` 各做兩件事：

1. `$fillable` 陣列加入 `'user_id'`（放在第一個位置）。
2. 新增 `user()` relation，並在檔案頂部補 `use App\Models\User;`（`OAuthState` 已使用 `BelongsTo`，無需另 import）：

```php
/**
 * The user who owns this record.
 *
 * @return BelongsTo<User, self>
 */
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

以 `Post` 為例，`$fillable` 開頭變為：

```php
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
```

- [ ] **步驟 1.4：更新 3 個 Factory**

`ThreadsAccountFactory`、`PostFactory`、`ReplyFactory` 的 `definition()` 第一個 key 加入 `'user_id' => User::factory()`，並在頂部補 `use App\Models\User;`。

`ThreadsAccountFactory` 範例：

```php
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'threads_app_id' => ThreadsApp::factory(),
            'threads_user_id' => fake()->unique()->numerify('##########'),
            // ...其餘不變
        ];
    }
```

- [ ] **步驟 1.5：執行 migration 驗證**

執行：`php artisan migrate`

預期：4 張表出現 `user_id` 欄位，且現有資料 `user_id = 2`。

驗證：`php artisan tinker --execute 'echo \App\Models\Post::count();'` 可正常執行（無錯誤）。

---

## 任務 2：PostService 與 ReplyService 加入 user scope

**檔案：**
- 修改：`app/Services/PostService.php`
- 修改：`app/Services/ReplyService.php`
- 測試：`tests/Feature/PostServiceTest.php`
- 測試：`tests/Feature/ReplyServiceTest.php`

- [ ] **步驟 2.1：編寫失敗測試（PostService）**

修改 `tests/Feature/PostServiceTest.php`，加入 user 隔離測試。先加 `use App\Models\User;`。

新增測試方法（放在類別最後）：

```php
    public function test_create_records_authenticated_user_id(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);

        $post = app(PostService::class)->create([
            'threads_account_id' => $account->id,
            'text' => '隔離測試',
            'scheduled_at' => now()->addHour(),
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_list_only_returns_own_posts(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->actingAs($userA);

        $accountA = ThreadsAccount::factory()->create(['user_id' => $userA->id]);
        $accountB = ThreadsAccount::factory()->create(['user_id' => $userB->id]);

        app(PostService::class)->create([
            'threads_account_id' => $accountA->id,
            'text' => 'A 的貼文',
            'scheduled_at' => now()->addHour(),
        ]);
        app(PostService::class)->create([
            'threads_account_id' => $accountB->id,
            'text' => 'B 的貼文',
            'scheduled_at' => now()->addHour(),
        ]);

        $result = app(PostService::class)->list();

        $this->assertCount(1, $result);
        $this->assertSame('A 的貼文', $result->first()->text);
    }
```

- [ ] **步驟 2.2：執行測試確認失敗**

執行：`php artisan test --compact --filter=test_create_records_authenticated_user_id`

預期：FAIL，因 `PostService::create()` 尚未寫入 `user_id`。

- [ ] **步驟 2.3：實作 PostService**

修改 `app/Services/PostService.php`：

頂部補 `use App\Models\ThreadsAccount;` 與 `use InvalidArgumentException;`。

```php
    public function create(array $data): Post
    {
        $userId = auth()->id();

        $account = ThreadsAccount::query()
            ->where('user_id', $userId)
            ->find($data['threads_account_id']);

        if ($account === null) {
            throw new InvalidArgumentException('帳號不存在或無權存取');
        }

        $post = new Post;
        $post->user_id = $userId;
        $post->threads_account_id = $data['threads_account_id'];
        $post->text = $data['text'];
        $post->scheduled_at = $data['scheduled_at'];
        $post->status = PostStatus::Scheduled;
        $post->save();

        return $post;
    }

    public function list(array $filters = [], ?int $userId = null): Collection
    {
        $userId ??= auth()->id();

        $query = Post::query()->with('threadsAccount')->where('user_id', $userId);

        if (! empty($filters['threads_account_id'])) {
            $query->where('threads_account_id', $filters['threads_account_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function find(int $id, ?int $userId = null): ?Post
    {
        $userId ??= auth()->id();

        return Post::query()->with('threadsAccount')->where('user_id', $userId)->find($id);
    }
```

- [ ] **步驟 2.4：執行測試確認通過**

執行：`php artisan test --compact --filter=test_create_records_authenticated_user_id`

預期：PASS。

- [ ] **步驟 2.5：編寫失敗測試（ReplyService）**

修改 `tests/Feature/ReplyServiceTest.php`，加入 user 隔離測試。加 `use App\Models\User;`。

```php
    public function test_create_post_reply_rejects_foreign_account(): void
    {
        Queue::fake();

        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->actingAs($userA);

        $foreignAccount = ThreadsAccount::factory()->create(['user_id' => $userB->id]);
        $post = Post::factory()->published()->create(['threads_account_id' => $foreignAccount->id, 'user_id' => $userA->id]);

        $this->expectException(InvalidArgumentException::class);

        app(ReplyService::class)->createPostReply($foreignAccount->id, $post->id, '內容');
    }

    public function test_list_only_returns_own_replies(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->actingAs($userA);

        $accountA = ThreadsAccount::factory()->create(['user_id' => $userA->id]);
        $postA = Post::factory()->published()->create(['threads_account_id' => $accountA->id, 'user_id' => $userA->id]);

        $replyOwn = Reply::factory()->create(['threads_account_id' => $accountA->id, 'post_id' => $postA->id, 'user_id' => $userA->id]);
        Reply::factory()->create(['user_id' => $userB->id]);

        $result = app(ReplyService::class)->list();

        $this->assertCount(1, $result);
        $this->assertSame($replyOwn->id, $result->first()->id);
    }
```

- [ ] **步驟 2.6：執行測試確認失敗**

執行：`php artisan test --compact --filter=test_create_post_reply_rejects_foreign_account`

預期：FAIL，因 `ReplyService::createPostReply()` 尚未驗證 account 歸屬。

- [ ] **步驟 2.7：實作 ReplyService**

修改 `app/Services/ReplyService.php`：

頂部補 `use App\Models\ThreadsAccount;`。

```php
    public function createPostReply(int $threadsAccountId, int $postId, string $text): Reply
    {
        $userId = auth()->id();

        $account = ThreadsAccount::query()
            ->where('user_id', $userId)
            ->find($threadsAccountId);

        if ($account === null) {
            throw new InvalidArgumentException('帳號不存在或無權存取');
        }

        $post = Post::query()
            ->where('user_id', $userId)
            ->find($postId);

        if ($post === null || $post->threads_media_id === null) {
            throw new InvalidArgumentException('目標貼文不存在或尚未發佈，無法回覆');
        }

        $reply = new Reply;
        $reply->user_id = $userId;
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

    public function list(array $filters = [], ?int $userId = null): Collection
    {
        $userId ??= auth()->id();

        $query = Reply::query()->with(['threadsAccount', 'post'])->where('user_id', $userId);

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
```

- [ ] **步驟 2.8：更新既有測試並執行**

依「測試隔離總規則」，更新 `PostServiceTest` 與 `ReplyServiceTest` 所有既有測試：
- 每個測試開頭 `$user = User::factory()->create(); $this->actingAs($user);`
- 所有 `ThreadsAccount::factory()->create()` 補 `['user_id' => $user->id]`
- `ReplyServiceTest` 中 `Post::factory()->...create([...])` 補 `'user_id' => $user->id`

執行：`php artisan test --compact tests/Feature/PostServiceTest.php tests/Feature/ReplyServiceTest.php`

預期：全數 PASS。

---

## 任務 3：Filament 後台資料隔離

**檔案：**
- 修改：`app/Filament/Resources/ThreadsAccounts/ThreadsAccountResource.php`
- 修改：`app/Filament/Resources/Posts/PostResource.php`
- 修改：`app/Filament/Resources/Replies/ReplyResource.php`
- 修改：`app/Filament/Resources/Posts/Schemas/PostForm.php`
- 修改：`app/Filament/Resources/Replies/Schemas/ReplyForm.php`
- 修改：`app/Filament/Resources/Posts/Pages/CreatePost.php`
- 測試：`tests/Feature/PostResourceTest.php`
- 測試：`tests/Feature/ReplyResourceTest.php`

- [ ] **步驟 3.1：編寫失敗測試（Resource 隔離）**

修改 `tests/Feature/PostResourceTest.php`，加隔離測試。加 `use App\Models\User;`（已存在，確認即可）。

```php
    public function test_list_posts_shows_own_posts_only(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $accountA = ThreadsAccount::factory()->create(['user_id' => $userA->id]);
        $accountB = ThreadsAccount::factory()->create(['user_id' => $userB->id]);

        $postA = Post::factory()->create(['threads_account_id' => $accountA->id, 'user_id' => $userA->id]);
        $postB = Post::factory()->create(['threads_account_id' => $accountB->id, 'user_id' => $userB->id]);

        Livewire::actingAs($userA)
            ->test(ListPosts::class)
            ->assertCanSeeTableRecords([$postA])
            ->assertCanNotSeeTableRecords([$postB]);
    }
```

- [ ] **步驟 3.2：執行測試確認失敗**

執行：`php artisan test --compact --filter=test_list_posts_shows_own_posts_only`

預期：FAIL，因 `PostResource` 尚未 scope。

- [ ] **步驟 3.3：實作 3 個 Resource 的 getEloquentQuery**

`ThreadsAccountResource`、`PostResource`、`ReplyResource` 各加：

頂部補 `use Illuminate\Database\Eloquent\Builder;`。

```php
    /**
     * 每位登入人員僅能看到自己的資料。
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }
```

- [ ] **步驟 3.4：實作 Form Select 的 scope**

`PostForm.php` 的 `threads_account_id` Select 改為（用 `relationship()` 第三參數 closure 做 scope，與現有 ReplyForm `post_id` 寫法一致）：

```php
                Select::make('threads_account_id')
                    ->label('目標帳號')
                    ->relationship('threadsAccount', 'username', fn ($query) => $query->where('user_id', auth()->id()))
                    ->getOptionLabelFromRecordUsing(fn ($record) => "@{$record->username}")
                    ->required()
                    ->helperText(fn ($get) => self::getAccountWarning($get('threads_account_id'))),
```

`ReplyForm.php` 的兩個 Select 改為：

```php
                Select::make('threads_account_id')
                    ->label('來源帳號')
                    ->relationship('threadsAccount', 'username', fn ($query) => $query->where('user_id', auth()->id()))
                    ->getOptionLabelFromRecordUsing(fn ($record) => "@{$record->username}")
                    ->required(),

                Select::make('post_id')
                    ->label('目標貼文')
                    ->relationship('post', 'text', fn ($query) => $query->where('user_id', auth()->id())->whereNotNull('threads_media_id'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => mb_strimwidth($record->text, 0, 40, '...'))
                    ->required(),
```

- [ ] **步驟 3.5：實作 CreatePost 寫入 user_id**

修改 `app/Filament/Resources/Posts/Pages/CreatePost.php` 的 `mutateFormDataBeforeCreate`：

```php
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['scheduled_at'])) {
            $data['status'] = PostStatus::Scheduled->value;
        }

        $data['user_id'] = auth()->id();

        return $data;
    }
```

（`CreateReply` 走 `ReplyService::createPostReply()`，已在任務 2 寫入 `user_id`，無需改動。）

- [ ] **步驟 3.6：執行測試確認通過**

執行：`php artisan test --compact --filter=test_list_posts_shows_own_posts_only`

預期：PASS。

- [ ] **步驟 3.7：更新既有測試並執行**

依「測試隔離總規則」更新 `PostResourceTest` 與 `ReplyResourceTest` 所有既有測試：
- `PostResourceTest` 中建立資料的測試：先 `$user = User::factory()->create()`，`$account = ThreadsAccount::factory()->create(['user_id' => $user->id])`，`Post::factory()->create(['threads_account_id' => $account->id, 'user_id' => $user->id])`，`Livewire::actingAs($user)`。
- `ReplyResourceTest` 中建立資料的測試：同樣先建立 `$user`，帳號與貼文補 `user_id => $user->id`，`Livewire::actingAs($user)`。

執行：`php artisan test --compact tests/Feature/PostResourceTest.php tests/Feature/ReplyResourceTest.php`

預期：全數 PASS。

---

## 任務 4：MCP 工具資料隔離

**檔案：**
- 修改：`app/Mcp/Tools/ListAccountsTool.php`
- 修改：`app/Mcp/Tools/CreatePostTool.php`
- 修改：`app/Mcp/Tools/CreateReplyTool.php`
- 測試：`tests/Feature/McpToolsTest.php`

> 說明：`ListPostsTool`、`GetPostTool`、`ListRepliesTool` 已透過 `PostService::list()/find()` 與 `ReplyService::list()` 的預設 `auth()->id()` scope 達成隔離，無需改動。

- [ ] **步驟 4.1：編寫失敗測試（ListAccounts 隔離）**

修改 `tests/Feature/McpToolsTest.php`，加入隔離測試。加 `use App\Models\User;`（確認已存在）。

```php
    public function test_list_accounts_returns_only_own_accounts(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        ThreadsAccount::factory()->create(['username' => 'alice', 'user_id' => $userA->id]);
        ThreadsAccount::factory()->create(['username' => 'bob', 'user_id' => $userB->id]);

        ThreadsMcpServer::actingAs($userA)
            ->tool(ListAccountsTool::class)
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('accounts', fn ($accounts) => count($accounts) === 1)
                ->where('accounts.0.username', 'alice')
                ->etc());
    }

    public function test_create_post_rejects_foreign_account(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $foreignAccount = ThreadsAccount::factory()->create(['user_id' => $userB->id]);

        ThreadsMcpServer::actingAs($userA)
            ->tool(CreatePostTool::class, [
                'threads_account_id' => $foreignAccount->id,
                'text' => '越權貼文',
                'scheduled_at' => now()->addHour()->toIso8601String(),
            ])->assertHasErrors();
    }
```

- [ ] **步驟 4.2：執行測試確認失敗**

執行：`php artisan test --compact --filter=test_list_accounts_returns_only_own_accounts`

預期：FAIL，因 `ListAccountsTool` 尚未 scope。

- [ ] **步驟 4.3：實作 ListAccountsTool**

修改 `app/Mcp/Tools/ListAccountsTool.php` 的 `handle()`：

```php
        $accounts = ThreadsAccount::query()
            ->where('user_id', auth()->id())
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderBy('id')
            ->get()
```

- [ ] **步驟 4.4：實作 CreatePostTool 與 CreateReplyTool 的歸屬驗證**

`CreatePostTool.php` 頂部補 `use Illuminate\Validation\Rule;`，並修改 `handle()` 的驗證：

```php
        $data = $request->validate([
            'threads_account_id' => ['required', 'integer', Rule::exists('threads_accounts', 'id')->where('user_id', auth()->id())],
            'text' => ['required', 'string', 'max:500'],
            'scheduled_at' => ['required', 'date'],
        ]);
```

`CreateReplyTool.php` 頂部補 `use Illuminate\Validation\Rule;`，並修改驗證：

```php
        $data = $request->validate([
            'threads_account_id' => ['required', 'integer', Rule::exists('threads_accounts', 'id')->where('user_id', auth()->id())],
            'post_id' => ['required', 'integer', Rule::exists('posts', 'id')->where('user_id', auth()->id())],
            'text' => ['required', 'string', 'max:500'],
        ]);
```

- [ ] **步驟 4.5：執行測試確認通過**

執行：`php artisan test --compact --filter=test_list_accounts_returns_only_own_accounts`

預期：PASS。

- [ ] **步驟 4.6：更新既有測試並執行**

依「測試隔離總規則」更新 `McpToolsTest` 所有既有測試：
- 每個測試建立 `$user = User::factory()->create()`，改用 `ThreadsMcpServer::actingAs($user)->tool(...)`。
- 所有 `ThreadsAccount::factory()->create()` / `Post::factory()->create()` / `Reply::factory()->create()` 補 `user_id => $user->id`。

執行：`php artisan test --compact tests/Feature/McpToolsTest.php`

預期：全數 PASS。

---

## 任務 5：OAuth 流程寫入與驗證 user_id

**檔案：**
- 修改：`app/Models/OAuthState.php`
- 修改：`app/Http/Controllers/ThreadsOAuthController.php`

- [ ] **步驟 5.1：實作 OAuthState 寫入 user_id**

修改 `app/Models/OAuthState.php` 的 `createForApp()`，在 `self::query()->create([...])` 中加入：

```php
            'user_id' => auth()->id(),
```

完整 `create()` 陣列變為：

```php
        self::query()->create([
            'token_hash' => hash('sha256', $token),
            'threads_app_id' => $app->id,
            'threads_account_id' => $account?->id,
            'user_id' => auth()->id(),
            'expires_at' => now()->addMinutes(10),
        ]);
```

- [ ] **步驟 5.2：實作 OAuthState resolve 驗證 user_id**

修改 `app/Models/OAuthState.php` 的 `resolve()`，在查詢中加入 user 條件：

```php
        $state = self::query()
            ->where('token_hash', hash('sha256', $token))
            ->where('user_id', auth()->id())
            ->first();
```

- [ ] **步驟 5.3：實作 ThreadsAccount 綁定寫入 user_id**

修改 `app/Http/Controllers/ThreadsOAuthController.php` 的 `callback()`，在 `$attributes` 陣列中加入：

```php
            $attributes = [
                'threads_app_id' => $app->id,
                'user_id' => auth()->id(),
                'username' => $profile['username'] ?? $profile['id'],
                // ...其餘不變
            ];
```

（此處 `auth()->id()` 與 OAuthState 驗證一致，確保綁定帳號歸屬正確使用者。）

- [ ] **步驟 5.4：驗證 OAuthState 邏輯**

執行：`php artisan test --compact --filter=ThreadsAppResourceTest`

預期：既有測試不受影響、全數 PASS（驗證未破壞 App 管理與 OAuth state 建立流程）。

---

## 任務 6：最終驗證與程式碼風格

- [ ] **步驟 6.1：執行完整測試套件**

執行：`php -d memory_limit=-1 vendor/bin/phpunit --no-coverage`

預期：全數 PASS（無 regression）。

- [ ] **步驟 6.2：修正程式碼風格**

執行：`vendor/bin/pint --dirty --format agent`

預期：無格式錯誤。

- [ ] **步驟 6.3：手動驗證資料隔離**

以 bill（id=1）登入後台，確認看不到任何 Threads 帳號、貼文、回覆；以 donnie（id=2）登入，確認看到全部現有資料。

---

## 變更檔案總覽

| 層級 | 檔案 | 動作 |
|------|------|------|
| Migration | `database/migrations/2026_08_18_10000x_*.php` × 5 | 建立 |
| Model | `app/Models/{ThreadsAccount,Post,Reply,OAuthState}.php` | 修改 |
| Factory | `database/factories/{ThreadsAccount,Post,Reply}Factory.php` | 修改 |
| Service | `app/Services/{PostService,ReplyService}.php` | 修改 |
| Filament | `app/Filament/Resources/...`（3 Resource + 2 Form + 1 Page） | 修改 |
| MCP | `app/Mcp/Tools/{ListAccounts,CreatePost,CreateReply}Tool.php` | 修改 |
| Controller | `app/Http/Controllers/ThreadsOAuthController.php` | 修改 |
| Test | `tests/Feature/{PostService,ReplyService,PostResource,ReplyResource,McpTools}Test.php` | 修改 |

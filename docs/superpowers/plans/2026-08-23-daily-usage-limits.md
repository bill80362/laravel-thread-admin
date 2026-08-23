# 每日用量限制與紀錄查詢 實作計畫

> **面向 AI 代理的工作者：** 必需子技能：使用 superpowers:subagent-driven-development（推薦）或 superpowers:executing-plans 逐任務實作此計畫。步驟使用複選框（`- [ ]`）語法來追蹤進度。

**目標：** 建立每日發文/回覆用量限制機制，包含 activity_logs 記錄、發送前檢查、MCP 軟性警告、介面用量條、Admin/User 明細查詢。

**架構：** 新增獨立 `activity_logs` 表記錄每次發送（刪除不減計數），在 Job stage1 檢查用量，超額則標記 Failed。MCP 工具建立時回傳軟性警告。User 列表頁頂部用量條。Admin 與 User 皆可查詢用量明細。

**技術棧：** Laravel 11, Filament 4, PHP 8.4, MySQL/MariaDB

---

## 檔案結構

### 新增檔案
- `database/migrations/2026_08_23_000001_create_activity_logs_table.php` — activity_logs 表（無 FK）
- `app/Models/ActivityLog.php` — ActivityLog Model
- `database/factories/ActivityLogFactory.php` — ActivityLog Factory
- `app/Filament/Resources/ActivityLogs/ActivityLogResource.php` — User 端發送紀錄 Resource
- `app/Filament/Resources/ActivityLogs/Pages/ListActivityLogs.php` — 列表頁
- `app/Filament/Resources/ActivityLogs/Tables/ActivityLogsTable.php` — 列表表格設定
- `app/Filament/Resources/Users/RelationManagers/ActivityLogsRelationManager.php` — Admin 端用量明細

### 修改檔案
- `app/Jobs/PublishScheduledPost.php` — stage1 用量檢查 + stage3 寫入 log
- `app/Jobs/PublishReply.php` — stage1 用量檢查 + stage2 寫入 log
- `app/Mcp/Tools/CreatePostTool.php` — 回傳軟性警告
- `app/Mcp/Tools/CreateReplyTool.php` — 回傳軟性警告
- `app/Filament/Resources/Posts/Pages/ListPosts.php` — 頂部用量提示條
- `app/Filament/Resources/Users/Tables/UsersTable.php` — 用量欄位可點擊
- `app/Filament/Resources/Users/UserResource.php` — 註冊 ActivityLogsRelationManager
- `app/Providers/Filament/UserPanelProvider.php` — 註冊 ActivityLogResource
- `tests/Feature/PublishScheduledPostTest.php` — 新增用量檢查測試
- `tests/Feature/PublishReplyTest.php` — 新增用量檢查測試

---

### 任務 1：Migration + Model + Factory

**檔案：**
- 建立：`database/migrations/2026_08_23_000001_create_activity_logs_table.php`
- 建立：`app/Models/ActivityLog.php`
- 建立：`database/factories/ActivityLogFactory.php`

- [ ] **步驟 1：建立 Migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('threads_account_id');
            $table->string('type'); // 'post' | 'reply'
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('threads_media_id')->nullable();
            $table->string('text', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
```

- [ ] **步驟 2：建立 ActivityLog Model**

```php
<?php

namespace App\Models;

use Database\Factories\ActivityLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    /** @use HasFactory<ActivityLogFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'threads_account_id',
        'type',
        'reference_id',
        'threads_media_id',
        'text',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function threadsAccount(): BelongsTo
    {
        return $this->belongsTo(ThreadsAccount::class);
    }

    /**
     * 篩選今日的記錄。
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * 計算某使用者今日特定類型的發送數量。
     */
    public static function countTodayForUser(int $userId, string $type): int
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->whereDate('created_at', today())
            ->count();
    }
}
```

- [ ] **步驟 3：建立 Factory**

```php
<?php

namespace Database\Factories;

use App\Models\ThreadsAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'threads_account_id' => ThreadsAccount::factory(),
            'type' => 'post',
            'reference_id' => null,
            'threads_media_id' => 'media-' . $this->faker->uuid(),
            'text' => $this->faker->sentence(),
        ];
    }

    public function post(): static
    {
        return $this->state(['type' => 'post']);
    }

    public function reply(): static
    {
        return $this->state(['type' => 'reply']);
    }
}
```

- [ ] **步驟 4：執行 migration 確認無誤**

執行：`php artisan migrate --pretend | head -20`
預期：顯示 CREATE TABLE activity_logs

- [ ] **步驟 5：執行測試確認 Model 可運作**

執行：`php artisan tinker --execute 'App\Models\ActivityLog::countTodayForUser(1, "post");'`
預期：回傳 0（無資料）

---

### 任務 2：Job 修改 — PublishScheduledPost 用量檢查 + Log 寫入

**檔案：**
- 修改：`app/Jobs/PublishScheduledPost.php`

- [ ] **步驟 1：在 stage1 加入用量檢查**

在 `PublishScheduledPost::handle()` 的 stage1 區塊，建立 container 之前，加入用量檢查：

```php
// --- Stage 1: 建立 container(s) ---
if ($isStage1) {
    // 檢查每日發文上限
    $user = $post->user;
    if ($user !== null && $user->max_daily_posts > 0) {
        $todayCount = \App\Models\ActivityLog::countTodayForUser($user->id, 'post');
        if ($todayCount >= $user->max_daily_posts) {
            $post->update([
                'status' => PostStatus::Failed,
                'error_message' => '已達每日發文上限',
            ]);
            return;
        }
    }

    $imageCount = $post->images->count();
    // ... 其餘程式碼不變 ...
```

- [ ] **步驟 2：在 stage3（發佈成功後）寫入 activity_log**

在 stage3 的 `$post->update([...])` 之後，加入：

```php
// --- Stage 3: 發佈 ---
$mediaId = $threads->publishContainer($account, $this->creationId);

$post->update([
    'status' => PostStatus::Published,
    'threads_media_id' => $mediaId,
    'published_at' => now(),
    'error_message' => null,
]);

// 寫入 activity_log
\App\Models\ActivityLog::create([
    'user_id' => $post->user_id,
    'threads_account_id' => $account->id,
    'type' => 'post',
    'reference_id' => $post->id,
    'threads_media_id' => $mediaId,
    'text' => $post->text,
]);
```

- [ ] **步驟 3：執行現有測試確認沒壞**

執行：`php artisan test --compact --filter=PublishScheduledPostTest`
預期：所有測試 PASS

---

### 任務 3：Job 修改 — PublishReply 用量檢查 + Log 寫入

**檔案：**
- 修改：`app/Jobs/PublishReply.php`

- [ ] **步驟 1：在 stage1 加入用量檢查**

在 `PublishReply::handle()` 的 stage1 區塊，建立 container 之前：

```php
if ($this->creationId === null) {
    // 檢查每日回覆上限
    $user = $reply->user;
    if ($user !== null && $user->max_daily_replies > 0) {
        $todayCount = \App\Models\ActivityLog::countTodayForUser($user->id, 'reply');
        if ($todayCount >= $user->max_daily_replies) {
            $reply->update([
                'status' => ReplyStatus::Failed,
                'error_message' => '已達每日回覆上限',
            ]);
            return;
        }
    }

    $text = $this->replyText ?? $reply->text;
    // ... 其餘程式碼不變 ...
```

- [ ] **步驟 2：在 stage2（發佈成功後）寫入 activity_log**

在 stage2 的 `$reply->update([...])` 之後：

```php
$threads->publishContainer($account, $this->creationId);

$reply->update([
    'status' => ReplyStatus::Replied,
    'replied_at' => now(),
    'error_message' => null,
]);

// 寫入 activity_log
\App\Models\ActivityLog::create([
    'user_id' => $reply->user_id,
    'threads_account_id' => $account->id,
    'type' => 'reply',
    'reference_id' => $reply->id,
    'threads_media_id' => null,
    'text' => $reply->text,
]);
```

- [ ] **步驟 3：執行現有測試確認沒壞**

執行：`php artisan test --compact --filter=PublishReplyTest`
預期：所有測試 PASS

---

### 任務 4：MCP 工具 — 軟性警告

**檔案：**
- 修改：`app/Mcp/Tools/CreatePostTool.php`
- 修改：`app/Mcp/Tools/CreateReplyTool.php`

- [ ] **步驟 1：修改 CreatePostTool 回傳 warnings**

在 `CreatePostTool::handle()` 的 `return Response::structured([...])` 之前，計算用量：

```php
use App\Models\ActivityLog;
use App\Models\Post;

// 計算軟性警告
$warnings = [];
$userId = auth()->id();
$user = auth()->user();

if ($user && $user->max_daily_posts > 0) {
    $todaySent = ActivityLog::countTodayForUser($userId, 'post');
    $todayScheduled = Post::query()
        ->where('user_id', $userId)
        ->where('status', \App\Enums\PostStatus::Scheduled)
        ->whereDate('scheduled_at', today())
        ->count();

    if ($todaySent > 0 || $todayScheduled > 0) {
        $parts = ["今日已發文 {$todaySent} 篇（上限 {$user->max_daily_posts}）"];
        if ($todayScheduled > 0) {
            $parts[] = "尚有 {$todayScheduled} 篇排程將於今日發送";
        }
        $warnings[] = implode('，', $parts);
    }
}

return Response::structured([
    'post' => [ /* ... 不變 ... */ ],
    'warnings' => $warnings,
]);
```

- [ ] **步驟 2：修改 CreateReplyTool 回傳 warnings**

在 `CreateReplyTool::handle()` 的 `return Response::structured([...])` 之前：

```php
use App\Models\ActivityLog;

$warnings = [];
$userId = auth()->id();
$user = auth()->user();

if ($user && $user->max_daily_replies > 0) {
    $todaySent = ActivityLog::countTodayForUser($userId, 'reply');
    if ($todaySent > 0) {
        $warnings[] = "今日已回覆 {$todaySent} 則（上限 {$user->max_daily_replies}）";
    }
}

return Response::structured([
    'reply' => [ /* ... 不變 ... */ ],
    'warnings' => $warnings,
]);
```

---

### 任務 5：User 端 — 列表頁用量提示條

**檔案：**
- 修改：`app/Filament/Resources/Posts/Pages/ListPosts.php`

- [ ] **步驟 1：在 ListPosts 頁面加入用量提示條方法**

在 `ListPosts` class 中加入計算用量資料的方法：

```php
use App\Models\ActivityLog;
use App\Models\Post;
use App\Enums\PostStatus;

/**
 * 取得今日用量資料供 Blade view 使用。
 */
public function getDailyUsageData(): array
{
    $userId = auth()->id();
    $user = auth()->user();

    $postSent = ActivityLog::countTodayForUser($userId, 'post');
    $postScheduled = Post::query()
        ->where('user_id', $userId)
        ->where('status', PostStatus::Scheduled)
        ->whereDate('scheduled_at', today())
        ->count();
    $replySent = ActivityLog::countTodayForUser($userId, 'reply');

    return [
        'post_sent' => $postSent,
        'post_max' => $user?->max_daily_posts ?? 0,
        'post_scheduled' => $postScheduled,
        'reply_sent' => $replySent,
        'reply_max' => $user?->max_daily_replies ?? 0,
    ];
}
```

- [ ] **步驟 2：修改 Blade view 加入用量條**

修改 `resources/views/filament/resources/posts/pages/list-posts.blade.php`：

```blade
<x-filament-panels::page>
    {{-- 用量提示條 --}}
    @php $usage = $this->getDailyUsageData(); @endphp
    <div class="mb-4 space-y-2">
        @if ($usage['post_max'] > 0)
            <div class="rounded-lg bg-white p-3 shadow-sm border border-gray-200">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium">📊 今日發文用量</span>
                    <span>{{ $usage['post_sent'] }}/{{ $usage['post_max'] }}</span>
                </div>
                <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-gray-100">
                    <div class="h-full rounded-full bg-primary-500 transition-all"
                         style="width: {{ min(100, ($usage['post_sent'] / max(1, $usage['post_max'])) * 100) }}%">
                    </div>
                </div>
                <div class="mt-1 text-xs text-gray-500">
                    已發送 {{ $usage['post_sent'] }} 篇
                    @if ($usage['post_scheduled'] > 0)
                        · 排程中今日將發送 {{ $usage['post_scheduled'] }} 篇
                    @endif
                    · 剩餘 {{ max(0, $usage['post_max'] - $usage['post_sent']) }} 篇
                </div>
            </div>
        @endif

        @if ($usage['reply_max'] > 0)
            <div class="rounded-lg bg-white p-3 shadow-sm border border-gray-200">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium">📊 今日回覆用量</span>
                    <span>{{ $usage['reply_sent'] }}/{{ $usage['reply_max'] }}</span>
                </div>
                <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-gray-100">
                    <div class="h-full rounded-full bg-success-500 transition-all"
                         style="width: {{ min(100, ($usage['reply_sent'] / max(1, $usage['reply_max'])) * 100) }}%">
                    </div>
                </div>
                <div class="mt-1 text-xs text-gray-500">
                    已回覆 {{ $usage['reply_sent'] }} 則
                    · 剩餘 {{ max(0, $usage['reply_max'] - $usage['reply_sent']) }} 則
                </div>
            </div>
        @endif
    </div>

    {{ $this->content }}

    @include('filament.resources.posts.pages.post-reply-drawer')
</x-filament-panels::page>
```

---

### 任務 6：User 端 — 發送紀錄頁面

**檔案：**
- 建立：`app/Filament/Resources/ActivityLogs/ActivityLogResource.php`
- 建立：`app/Filament/Resources/ActivityLogs/Pages/ListActivityLogs.php`
- 建立：`app/Filament/Resources/ActivityLogs/Tables/ActivityLogsTable.php`
- 修改：`app/Providers/Filament/UserPanelProvider.php`

- [ ] **步驟 1：建立 ActivityLogResource**

```php
<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Filament\Resources\ActivityLogs\Tables\ActivityLogsTable;
use App\Models\ActivityLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = '發送紀錄';

    protected static ?string $modelLabel = '發送紀錄';

    protected static ?string $pluralModelLabel = '發送紀錄';

    protected static ?int $navigationSort = 50;

    public static function table(Table $table): Table
    {
        return ActivityLogsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
        ];
    }
}
```

- [ ] **步驟 2：建立 ListActivityLogs 頁面**

```php
<?php

namespace App\Filament\Resources\ActivityLogs\Pages;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use Filament\Resources\Pages\ListRecords;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;
}
```

- [ ] **步驟 3：建立 ActivityLogsTable**

```php
<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('發送時間')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('類型')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'post' ? 'primary' : 'success')
                    ->formatStateUsing(fn (string $state): string => $state === 'post' ? '貼文' : '回覆'),

                TextColumn::make('threadsAccount.username')
                    ->label('帳號')
                    ->formatStateUsing(fn (?string $state): string => $state ? "@{$state}" : '-'),

                TextColumn::make('text')
                    ->label('內容')
                    ->limit(50)
                    ->placeholder('（內容已刪除）'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('類型')
                    ->options([
                        'post' => '貼文',
                        'reply' => '回覆',
                    ]),
            ])
            ->actions([]);
    }
}
```

- [ ] **步驟 4：在 UserPanelProvider 註冊 Resource**

在 `app/Providers/Filament/UserPanelProvider.php` 的 `resources()` 陣列中加入：

```php
->resources([
    ThreadsAccountResource::class,
    PostResource::class,
    ReplyResource::class,
    McpTokenResource::class,
    \App\Filament\Resources\ActivityLogs\ActivityLogResource::class,
])
```

---

### 任務 7：Admin 端 — 用量明細查詢

**檔案：**
- 建立：`app/Filament/Resources/Users/RelationManagers/ActivityLogsRelationManager.php`
- 修改：`app/Filament/Resources/Users/UserResource.php`
- 修改：`app/Filament/Resources/Users/Tables/UsersTable.php`

- [ ] **步驟 1：建立 ActivityLogsRelationManager**

```php
<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivityLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'activityLogs';

    protected static ?string $title = '發送紀錄';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('發送時間')
                    ->dateTime('Y-m-d H:i:s'),

                TextColumn::make('type')
                    ->label('類型')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'post' ? 'primary' : 'success')
                    ->formatStateUsing(fn (string $state): string => $state === 'post' ? '貼文' : '回覆'),

                TextColumn::make('threadsAccount.username')
                    ->label('帳號')
                    ->formatStateUsing(fn (?string $state): string => $state ? "@{$state}" : '-'),

                TextColumn::make('text')
                    ->label('內容')
                    ->limit(50)
                    ->placeholder('（內容已刪除）'),

                TextColumn::make('reference_id')
                    ->label('關聯 ID')
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('類型')
                    ->options([
                        'post' => '貼文',
                        'reply' => '回覆',
                    ]),
            ]);
    }
}
```

- [ ] **步驟 2：在 User Model 加入 activityLogs 關聯**

在 `app/Models/User.php` 加入：

```php
/**
 * The activity logs for this user.
 */
public function activityLogs(): HasMany
{
    return $this->hasMany(ActivityLog::class);
}
```

- [ ] **步驟 3：在 UserResource 註冊 RelationManager**

在 `app/Filament/Resources/Users/UserResource.php` 的 `getRelations()` 中加入：

```php
public static function getRelations(): array
{
    return [
        ThreadsAccountsRelationManager::class,
        ActivityLogsRelationManager::class,
    ];
}
```

- [ ] **步驟 4：修改 UsersTable 用量欄位可點擊**

修改 `app/Filament/Resources/Users/Tables/UsersTable.php` 的 `daily_post_usage` 和 `daily_reply_usage` 欄位，加上 `url()` 指向 Edit 頁面的 RelationManager Tab：

```php
use Filament\Tables\Columns\TextColumn;

TextColumn::make('daily_post_usage')
    ->label('今日發文')
    ->state(fn (User $record): string => sprintf(
        '%d/%d',
        \App\Models\ActivityLog::countTodayForUser($record->id, 'post'),
        $record->max_daily_posts,
    ))
    ->url(fn (User $record): string => \App\Filament\Resources\Users\UserResource::getUrl('edit', [
        'record' => $record,
    ]) . '?activeRelationManager=2'),  // 假設 ActivityLogsRelationManager 是第 3 個 tab（index 2）

TextColumn::make('daily_reply_usage')
    ->label('今日回覆')
    ->state(fn (User $record): string => sprintf(
        '%d/%d',
        \App\Models\ActivityLog::countTodayForUser($record->id, 'reply'),
        $record->max_daily_replies,
    ))
    ->url(fn (User $record): string => \App\Filament\Resources\Users\UserResource::getUrl('edit', [
        'record' => $record,
    ]) . '?activeRelationManager=2'),
```

---

### 任務 8：測試

**檔案：**
- 修改：`tests/Feature/PublishScheduledPostTest.php`
- 修改：`tests/Feature/PublishReplyTest.php`

- [ ] **步驟 1：測試 PublishScheduledPost 超額時標記 Failed**

```php
public function test_daily_limit_blocks_post_when_exceeded(): void
{
    $user = \App\Models\User::factory()->create(['max_daily_posts' => 1]);
    $account = \App\Models\ThreadsAccount::factory()->create(['user_id' => $user->id]);
    $post = Post::factory()->create([
        'user_id' => $user->id,
        'threads_account_id' => $account->id,
        'status' => PostStatus::Scheduled,
        'scheduled_at' => now()->subMinute(),
    ]);

    // 先建立一筆 activity_log 讓今日已達上限
    \App\Models\ActivityLog::factory()->post()->create([
        'user_id' => $user->id,
        'threads_account_id' => $account->id,
        'created_at' => now(),
    ]);

    $threads = Mockery::mock(ThreadsClient::class);
    $threads->shouldReceive('createTextContainer')->never();
    $threads->shouldReceive('publishContainer')->never();

    $job = new PublishScheduledPost($post->id);
    $job->handle($threads);

    $post->refresh();

    $this->assertSame(PostStatus::Failed, $post->status);
    $this->assertSame('已達每日發文上限', $post->error_message);
}
```

- [ ] **步驟 2：測試 PublishScheduledPost 成功後寫入 activity_log**

```php
public function test_successful_publish_creates_activity_log(): void
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
        ->andReturn('media-id-123');

    $job = new PublishScheduledPost($post->id, 'creation-id');
    $job->handle($threads);

    $this->assertDatabaseHas('activity_logs', [
        'user_id' => $post->user_id,
        'threads_account_id' => $account->id,
        'type' => 'post',
        'reference_id' => $post->id,
        'threads_media_id' => 'media-id-123',
        'text' => $post->text,
    ]);
}
```

- [ ] **步驟 3：測試 PublishReply 超額時標記 Failed**

```php
public function test_daily_limit_blocks_reply_when_exceeded(): void
{
    $user = \App\Models\User::factory()->create(['max_daily_replies' => 1]);
    $account = \App\Models\ThreadsAccount::factory()->create(['user_id' => $user->id]);
    $reply = Reply::factory()->create([
        'user_id' => $user->id,
        'threads_account_id' => $account->id,
        'status' => ReplyStatus::New,
    ]);

    // 先建立一筆 activity_log 讓今日已達上限
    \App\Models\ActivityLog::factory()->reply()->create([
        'user_id' => $user->id,
        'threads_account_id' => $account->id,
        'created_at' => now(),
    ]);

    $threads = Mockery::mock(ThreadsClient::class);
    $threads->shouldReceive('createTextContainer')->never();
    $threads->shouldReceive('publishContainer')->never();

    $job = new PublishReply($reply->id);
    $job->handle($threads, app(\App\Services\ReplyService::class));

    $reply->refresh();

    $this->assertSame(ReplyStatus::Failed, $reply->status);
    $this->assertSame('已達每日回覆上限', $reply->error_message);
}
```

- [ ] **步驟 4：測試 PublishReply 成功後寫入 activity_log**

```php
public function test_successful_reply_creates_activity_log(): void
{
    $account = ThreadsAccount::factory()->create();
    $reply = Reply::factory()->create([
        'threads_account_id' => $account->id,
        'status' => ReplyStatus::Publishing,
    ]);

    $threads = Mockery::mock(ThreadsClient::class);
    $threads->shouldReceive('publishContainer')
        ->once()
        ->andReturn('media-id-456');

    $job = new PublishReply($reply->id, 'creation-id');
    $job->handle($threads, app(\App\Services\ReplyService::class));

    $this->assertDatabaseHas('activity_logs', [
        'user_id' => $reply->user_id,
        'threads_account_id' => $account->id,
        'type' => 'reply',
        'reference_id' => $reply->id,
        'text' => $reply->text,
    ]);
}
```

- [ ] **步驟 5：執行所有測試確認通過**

執行：`php artisan test --compact --filter="PublishScheduledPostTest|PublishReplyTest"`
預期：所有測試 PASS

---

### 任務 9：最終驗證

- [ ] **步驟 1：執行 Pint 格式化**

執行：`vendor/bin/pint --format agent`
預期：無錯誤

- [ ] **步驟 2：執行完整測試套件**

執行：`php artisan test --compact`
預期：所有測試 PASS

- [ ] **步驟 3：檢查 migration 狀態**

執行：`php artisan migrate:status`
預期：activity_logs 表 migration 顯示為「已執行」

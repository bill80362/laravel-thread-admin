<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\Post;
use App\Models\ThreadsAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PostListFiltersSortingTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_posts_can_filter_by_status(): void
    {
        $user = User::factory()->create();
        $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);

        $published = Post::factory()->published()->create([
            'user_id' => $user->id,
            'threads_account_id' => $account->id,
        ]);
        $draft = Post::factory()->create([
            'user_id' => $user->id,
            'threads_account_id' => $account->id,
            'status' => PostStatus::Draft,
        ]);

        Livewire::actingAs($user)
            ->test(ListPosts::class)
            ->assertCanSeeTableRecords([$published, $draft])
            ->filterTable('status', PostStatus::Published->value)
            ->assertCanSeeTableRecords([$published])
            ->assertCanNotSeeTableRecords([$draft]);
    }

    public function test_list_posts_can_filter_by_account(): void
    {
        $user = User::factory()->create();
        $accountA = ThreadsAccount::factory()->create(['user_id' => $user->id]);
        $accountB = ThreadsAccount::factory()->create(['user_id' => $user->id]);

        $postA = Post::factory()->create([
            'user_id' => $user->id,
            'threads_account_id' => $accountA->id,
        ]);
        $postB = Post::factory()->create([
            'user_id' => $user->id,
            'threads_account_id' => $accountB->id,
        ]);

        Livewire::actingAs($user)
            ->test(ListPosts::class)
            ->assertCanSeeTableRecords([$postA, $postB])
            ->filterTable('threads_account_id', $accountA->id)
            ->assertCanSeeTableRecords([$postA])
            ->assertCanNotSeeTableRecords([$postB]);
    }

    public function test_list_posts_can_filter_by_text_keyword(): void
    {
        $user = User::factory()->create();
        $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);

        $matching = Post::factory()->create([
            'user_id' => $user->id,
            'threads_account_id' => $account->id,
            'text' => '這是一篇包含特殊關鍵字的貼文',
        ]);
        $other = Post::factory()->create([
            'user_id' => $user->id,
            'threads_account_id' => $account->id,
            'text' => '完全不同的內容',
        ]);

        Livewire::actingAs($user)
            ->test(ListPosts::class)
            ->assertCanSeeTableRecords([$matching, $other])
            ->filterTable('text_search', ['text' => '特殊關鍵字'])
            ->assertCanSeeTableRecords([$matching])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_list_posts_can_filter_by_error_message_keyword(): void
    {
        $user = User::factory()->create();
        $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);

        $failed = Post::factory()->failed()->create([
            'user_id' => $user->id,
            'threads_account_id' => $account->id,
        ]);
        $ok = Post::factory()->create([
            'user_id' => $user->id,
            'threads_account_id' => $account->id,
        ]);

        Livewire::actingAs($user)
            ->test(ListPosts::class)
            ->assertCanSeeTableRecords([$failed, $ok])
            ->filterTable('error_search', ['error_message' => 'token'])
            ->assertCanSeeTableRecords([$failed])
            ->assertCanNotSeeTableRecords([$ok]);
    }

    public function test_list_posts_can_sort_by_scheduled_at(): void
    {
        $user = User::factory()->create();
        $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);

        $posts = collect([
            Post::factory()->create([
                'user_id' => $user->id,
                'threads_account_id' => $account->id,
                'scheduled_at' => now()->addDays(1),
            ]),
            Post::factory()->create([
                'user_id' => $user->id,
                'threads_account_id' => $account->id,
                'scheduled_at' => now()->addDays(2),
            ]),
            Post::factory()->create([
                'user_id' => $user->id,
                'threads_account_id' => $account->id,
                'scheduled_at' => now()->addDays(3),
            ]),
        ]);

        $sortedAsc = Post::query()->where('user_id', $user->id)->orderBy('scheduled_at')->get();
        $sortedDesc = Post::query()->where('user_id', $user->id)->orderBy('scheduled_at', 'desc')->get();

        Livewire::actingAs($user)
            ->test(ListPosts::class)
            ->sortTable('scheduled_at')
            ->assertCanSeeTableRecords($sortedAsc, inOrder: true)
            ->sortTable('scheduled_at', 'desc')
            ->assertCanSeeTableRecords($sortedDesc, inOrder: true);
    }

    public function test_list_posts_defaults_to_scheduled_at_desc(): void
    {
        $user = User::factory()->create();
        $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);

        // 建立三篇排程時間不同的貼文
        Post::factory()->create([
            'user_id' => $user->id,
            'threads_account_id' => $account->id,
            'scheduled_at' => now()->addDays(1),
        ]);
        Post::factory()->create([
            'user_id' => $user->id,
            'threads_account_id' => $account->id,
            'scheduled_at' => now()->addDays(2),
        ]);
        Post::factory()->create([
            'user_id' => $user->id,
            'threads_account_id' => $account->id,
            'scheduled_at' => now()->addDays(3),
        ]);

        // 預設排序：排程時間反向（最新在前）
        $expectedOrder = Post::query()
            ->where('user_id', $user->id)
            ->orderByRaw('scheduled_at IS NULL')
            ->orderBy('scheduled_at', 'desc')
            ->get();

        Livewire::actingAs($user)
            ->test(ListPosts::class)
            ->assertCanSeeTableRecords($expectedOrder, inOrder: true);
    }
}

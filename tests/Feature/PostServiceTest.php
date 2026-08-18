<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\ThreadsAccount;
use App\Models\User;
use App\Services\PostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_sets_scheduled_status(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);

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

    public function test_list_filters_by_account(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);
        $other = ThreadsAccount::factory()->create(['user_id' => $user->id]);

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
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->assertNull(app(PostService::class)->find(999999));
    }

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

        // 直接建立 userB 的貼文（不透過 Service，因為 Service 會驗證歸屬）
        Post::factory()->create([
            'user_id' => $userB->id,
            'threads_account_id' => $accountB->id,
            'text' => 'B 的貼文',
        ]);

        $result = app(PostService::class)->list();

        $this->assertCount(1, $result);
        $this->assertSame('A 的貼文', $result->first()->text);
    }
}

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

    public function test_list_filters_by_account(): void
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

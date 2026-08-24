<?php

namespace Tests\Feature;

use App\Console\Commands\DeduplicateReplies;
use App\Enums\ReplySource;
use App\Enums\ReplyStatus;
use App\Models\Post;
use App\Models\Reply;
use App\Models\ThreadsAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeduplicateRepliesTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_reports_nothing_to_clean(): void
    {
        $this->artisan(DeduplicateReplies::class)
            ->expectsOutput('沒有需要清理的重複回覆。')
            ->assertSuccessful();
    }

    public function test_command_deletes_duplicate_polling_replies_and_backfills_id(): void
    {
        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->published()->create(['threads_account_id' => $account->id]);

        // 原始手動回覆（無 threads_reply_id）
        $original = Reply::factory()->create([
            'threads_account_id' => $account->id,
            'post_id' => $post->id,
            'threads_reply_id' => null,
            'text' => '測試重複內容',
            'source' => ReplySource::Manual,
            'status' => ReplyStatus::Replied,
            'replied_at' => now(),
        ]);

        // 排程重複抓回來的 polling 回覆（有 threads_reply_id）
        $duplicate = Reply::factory()->create([
            'threads_account_id' => $account->id,
            'post_id' => $post->id,
            'threads_reply_id' => 'threads-media-id-123',
            'text' => '測試重複內容',
            'source' => ReplySource::Polling,
            'status' => ReplyStatus::New,
        ]);

        // 正常應存在的其他回覆（不應被刪除）
        $other = Reply::factory()->create([
            'threads_account_id' => $account->id,
            'post_id' => $post->id,
            'source' => ReplySource::Polling,
            'status' => ReplyStatus::New,
        ]);

        $this->artisan(DeduplicateReplies::class)
            ->assertSuccessful();

        // 重複的 polling 記錄應被刪除
        $this->assertDatabaseMissing('replies', ['id' => $duplicate->id]);

        // 原始手動回覆的 threads_reply_id 應被回填
        $original->refresh();
        $this->assertSame('threads-media-id-123', $original->threads_reply_id);

        // 其他回覆不應被刪除
        $this->assertDatabaseHas('replies', ['id' => $other->id]);
    }

    public function test_command_handles_duplicate_without_threads_reply_id(): void
    {
        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->published()->create(['threads_account_id' => $account->id]);

        // 原始手動回覆
        $original = Reply::factory()->create([
            'threads_account_id' => $account->id,
            'post_id' => $post->id,
            'threads_reply_id' => null,
            'text' => '另一筆重複',
            'source' => ReplySource::Manual,
            'status' => ReplyStatus::Replied,
            'replied_at' => now(),
        ]);

        // 重複的 polling 記錄但連 threads_reply_id 也是 null（極端情況）
        $duplicate = Reply::factory()->create([
            'threads_account_id' => $account->id,
            'post_id' => $post->id,
            'threads_reply_id' => null,
            'text' => '另一筆重複',
            'source' => ReplySource::Polling,
            'status' => ReplyStatus::New,
        ]);

        $this->artisan(DeduplicateReplies::class)
            ->assertSuccessful();

        // 重複記錄應被刪除
        $this->assertDatabaseMissing('replies', ['id' => $duplicate->id]);

        // 原始回覆仍為 null，沒有回填
        $original->refresh();
        $this->assertNull($original->threads_reply_id);
    }
}

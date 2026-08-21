<?php

namespace Tests\Feature;

use App\Enums\ReplyStatus;
use App\Enums\ThreadsAccountStatus;
use App\Exceptions\ThreadsApiException;
use App\Jobs\CollectThreadsReplies;
use App\Models\Post;
use App\Models\Reply;
use App\Models\ThreadsAccount;
use App\Services\ThreadsClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CollectThreadsRepliesTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_replies_are_inserted(): void
    {
        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->published()->create([
            'threads_account_id' => $account->id,
        ]);

        $threads = Mockery::mock(ThreadsClient::class);
        $threads->shouldReceive('getReplies')
            ->once()
            ->andReturn([
                ['id' => 'reply-1', 'username' => 'user1', 'text' => 'hi'],
                ['id' => 'reply-2', 'username' => 'user2', 'text' => 'hello'],
            ]);

        $job = new CollectThreadsReplies;
        $job->handle($threads);

        $this->assertDatabaseHas('replies', [
            'threads_reply_id' => 'reply-1',
            'threads_account_id' => $account->id,
            'post_id' => $post->id,
            'status' => ReplyStatus::New->value,
        ]);
        $this->assertDatabaseHas('replies', [
            'threads_reply_id' => 'reply-2',
        ]);
        $this->assertSame(2, Reply::query()->count());
    }

    public function test_new_replies_are_inserted_as_unread(): void
    {
        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->published()->create([
            'threads_account_id' => $account->id,
        ]);

        $threads = Mockery::mock(ThreadsClient::class);
        $threads->shouldReceive('getReplies')
            ->once()
            ->andReturn([
                ['id' => 'reply-unread', 'username' => 'user1', 'text' => 'hi'],
            ]);

        $job = new CollectThreadsReplies;
        $job->handle($threads);

        $reply = Reply::query()->where('threads_reply_id', 'reply-unread')->firstOrFail();
        $this->assertNull($reply->read_at);
    }

    public function test_duplicate_replies_are_not_inserted(): void
    {
        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->published()->create([
            'threads_account_id' => $account->id,
        ]);

        Reply::factory()->create([
            'threads_account_id' => $account->id,
            'post_id' => $post->id,
            'threads_reply_id' => 'reply-existing',
        ]);

        $threads = Mockery::mock(ThreadsClient::class);
        $threads->shouldReceive('getReplies')
            ->once()
            ->andReturn([
                ['id' => 'reply-existing', 'username' => 'user1', 'text' => 'hi'],
                ['id' => 'reply-new', 'username' => 'user2', 'text' => 'hello'],
            ]);

        $job = new CollectThreadsReplies;
        $job->handle($threads);

        $this->assertSame(2, Reply::query()->count());
        $this->assertDatabaseHas('replies', ['threads_reply_id' => 'reply-new']);
    }

    public function test_token_invalid_marks_account_needs_reauth(): void
    {
        $account = ThreadsAccount::factory()->create();
        Post::factory()->published()->create([
            'threads_account_id' => $account->id,
        ]);

        $threads = Mockery::mock(ThreadsClient::class);
        $threads->shouldReceive('getReplies')
            ->once()
            ->andThrow(new ThreadsApiException('Invalid OAuth access token', 190, 401));

        $job = new CollectThreadsReplies;
        $job->handle($threads);

        $account->refresh();

        $this->assertSame(ThreadsAccountStatus::NeedsReauth, $account->status);
    }

    public function test_recently_synced_account_is_skipped(): void
    {
        $account = ThreadsAccount::factory()->create([
            'last_synced_at' => now()->subMinute(),
        ]);
        Post::factory()->published()->create([
            'threads_account_id' => $account->id,
        ]);

        $threads = Mockery::mock(ThreadsClient::class);
        $threads->shouldReceive('getReplies')->never();

        $job = new CollectThreadsReplies;
        $job->handle($threads);

        $this->assertSame(0, Reply::query()->count());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

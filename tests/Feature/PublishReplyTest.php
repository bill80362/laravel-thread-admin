<?php

namespace Tests\Feature;

use App\Enums\ReplyStatus;
use App\Enums\ThreadsAccountStatus;
use App\Exceptions\ThreadsApiException;
use App\Jobs\PublishReply;
use App\Models\ActivityLog;
use App\Models\Post;
use App\Models\Reply;
use App\Models\ThreadsAccount;
use App\Models\User;
use App\Services\ReplyService;
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
            ->andReturn('creation-id-123');
        $threads->shouldReceive('publishContainer')->never();

        $job = new PublishReply($reply->id);
        $job->handle($threads, app(ReplyService::class));

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
        $job->handle($threads, app(ReplyService::class));

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
        $job->handle($threads, app(ReplyService::class));

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
        $job->handle($threads, app(ReplyService::class));

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
        $job->handle($threads, app(ReplyService::class));

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
        $job->handle($threads, app(ReplyService::class));

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
        $job->handle($threads, app(ReplyService::class));

        $reply->refresh();

        $this->assertSame(ReplyStatus::Replied, $reply->status);
    }

    public function test_daily_limit_blocks_reply_when_exceeded(): void
    {
        $user = User::factory()->create(['max_daily_replies' => 1]);
        $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);
        $reply = Reply::factory()->create([
            'user_id' => $user->id,
            'threads_account_id' => $account->id,
            'status' => ReplyStatus::New,
        ]);

        // 先建立一筆 activity_log 讓今日已達上限
        ActivityLog::factory()->reply()->create([
            'user_id' => $user->id,
            'threads_account_id' => $account->id,
            'created_at' => now(),
        ]);

        $threads = Mockery::mock(ThreadsClient::class);
        $threads->shouldReceive('createTextContainer')->never();
        $threads->shouldReceive('publishContainer')->never();

        $job = new PublishReply($reply->id);
        $job->handle($threads, app(ReplyService::class));

        $reply->refresh();

        $this->assertSame(ReplyStatus::Failed, $reply->status);
        $this->assertSame('已達每日回覆上限', $reply->error_message);
    }

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
        $job->handle($threads, app(ReplyService::class));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $reply->user_id,
            'threads_account_id' => $account->id,
            'type' => 'reply',
            'reference_id' => $reply->id,
            'text' => $reply->text,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

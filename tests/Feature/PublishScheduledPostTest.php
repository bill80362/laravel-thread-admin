<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Enums\ThreadsAccountStatus;
use App\Exceptions\ThreadsApiException;
use App\Jobs\PublishScheduledPost;
use App\Models\Post;
use App\Models\ThreadsAccount;
use App\Services\ThreadsClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class PublishScheduledPostTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_stage_creates_container_and_sets_status_to_publishing(): void
    {
        Queue::fake();

        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->create([
            'threads_account_id' => $account->id,
            'status' => PostStatus::Scheduled,
            'scheduled_at' => now()->subMinute(),
        ]);

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

    public function test_successful_publish_updates_post_status(): void
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

        $post->refresh();

        $this->assertSame(PostStatus::Published, $post->status);
        $this->assertSame('media-id-123', $post->threads_media_id);
        $this->assertNotNull($post->published_at);
    }

    public function test_token_invalid_marks_account_needs_reauth(): void
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
            ->andThrow(new ThreadsApiException('Invalid OAuth access token', 190, 401));

        $job = new PublishScheduledPost($post->id, 'creation-id');
        $job->handle($threads);

        $post->refresh();
        $account->refresh();

        $this->assertSame(PostStatus::Failed, $post->status);
        $this->assertSame('token 失效', $post->error_message);
        $this->assertSame(ThreadsAccountStatus::NeedsReauth, $account->status);
    }

    public function test_rate_limit_marks_post_failed(): void
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
            ->andThrow(new ThreadsApiException('Application request limit reached', 4, 429));

        $job = new PublishScheduledPost($post->id, 'creation-id');
        $job->handle($threads);

        $post->refresh();

        $this->assertSame(PostStatus::Failed, $post->status);
        $this->assertSame('已達每日發文上限', $post->error_message);
    }

    public function test_non_scheduled_post_is_skipped(): void
    {
        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->published()->create([
            'threads_account_id' => $account->id,
        ]);

        $threads = Mockery::mock(ThreadsClient::class);
        $threads->shouldReceive('publishContainer')->never();

        $job = new PublishScheduledPost($post->id, 'creation-id');
        $job->handle($threads);

        $post->refresh();

        $this->assertSame(PostStatus::Published, $post->status);
    }

    public function test_retryable_error_redispatches_and_increments_attempts(): void
    {
        Queue::fake();

        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->create([
            'threads_account_id' => $account->id,
            'status' => PostStatus::Scheduled,
            'scheduled_at' => now()->subMinute(),
            'publish_attempts' => 0,
        ]);

        $threads = Mockery::mock(ThreadsClient::class);
        $threads->shouldReceive('createTextContainer')
            ->once()
            ->andThrow(new ThreadsApiException('The requested resource does not exist', null, null));

        $job = new PublishScheduledPost($post->id);
        $job->handle($threads);

        $post->refresh();

        $this->assertSame(1, $post->publish_attempts);
        $this->assertSame(PostStatus::Scheduled, $post->status);
        Queue::assertPushed(PublishScheduledPost::class, 1);
    }

    public function test_retryable_error_at_max_attempts_marks_failed(): void
    {
        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->create([
            'threads_account_id' => $account->id,
            'status' => PostStatus::Scheduled,
            'scheduled_at' => now()->subMinute(),
            'publish_attempts' => PublishScheduledPost::MAX_PUBLISH_ATTEMPTS,
        ]);

        $threads = Mockery::mock(ThreadsClient::class);
        $threads->shouldReceive('createTextContainer')
            ->once()
            ->andThrow(new ThreadsApiException('The requested resource does not exist', null, null));

        $job = new PublishScheduledPost($post->id);
        $job->handle($threads);

        $post->refresh();

        $this->assertSame(PostStatus::Failed, $post->status);
        $this->assertSame('The requested resource does not exist', $post->error_message);
    }

    public function test_permanent_error_does_not_retry(): void
    {
        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->create([
            'threads_account_id' => $account->id,
            'status' => PostStatus::Scheduled,
            'scheduled_at' => now()->subMinute(),
            'publish_attempts' => 0,
        ]);

        $threads = Mockery::mock(ThreadsClient::class);
        $threads->shouldReceive('createTextContainer')
            ->once()
            ->andThrow(new ThreadsApiException('Invalid OAuth access token', 190, 401));

        $job = new PublishScheduledPost($post->id);
        $job->handle($threads);

        $post->refresh();

        $this->assertSame(PostStatus::Failed, $post->status);
        $this->assertSame('token 失效', $post->error_message);
        $this->assertSame(0, $post->publish_attempts);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

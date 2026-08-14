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
use Mockery;
use Tests\TestCase;

class PublishScheduledPostTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_publish_updates_post_status(): void
    {
        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->create([
            'threads_account_id' => $account->id,
            'status' => PostStatus::Scheduled,
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
            'status' => PostStatus::Scheduled,
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
            'status' => PostStatus::Scheduled,
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

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

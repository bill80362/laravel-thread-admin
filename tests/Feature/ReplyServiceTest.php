<?php

namespace Tests\Feature;

use App\Enums\ReplySource;
use App\Enums\ReplyStatus;
use App\Jobs\PublishReply;
use App\Models\Post;
use App\Models\Reply;
use App\Models\ThreadsAccount;
use App\Services\ReplyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use Tests\TestCase;

class ReplyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_post_reply_dispatches_publish_job(): void
    {
        Queue::fake();

        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->published()->create(['threads_account_id' => $account->id]);

        $reply = app(ReplyService::class)->createPostReply($account->id, $post->id, '貼文回覆內容');

        $this->assertDatabaseHas('replies', [
            'id' => $reply->id,
            'threads_account_id' => $account->id,
            'post_id' => $post->id,
            'threads_reply_id' => null,
            'text' => '貼文回覆內容',
            'source' => ReplySource::Manual->value,
            'status' => ReplyStatus::New->value,
        ]);

        Queue::assertPushed(PublishReply::class, fn ($job) => $job->replyId === $reply->id);
    }

    public function test_create_post_reply_rejects_unpublished_post(): void
    {
        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->create(['threads_account_id' => $account->id, 'threads_media_id' => null]);

        $this->expectException(InvalidArgumentException::class);

        app(ReplyService::class)->createPostReply($account->id, $post->id, '內容');
    }

    public function test_publish_dispatches_job_with_text(): void
    {
        Queue::fake();

        $account = ThreadsAccount::factory()->create();
        $reply = Reply::factory()->create([
            'threads_account_id' => $account->id,
            'threads_reply_id' => '12345',
            'status' => ReplyStatus::New,
        ]);

        app(ReplyService::class)->publish($reply, '回應內容');

        Queue::assertPushed(PublishReply::class, function ($job) use ($reply) {
            return $job->replyId === $reply->id && $job->replyText === '回應內容';
        });
    }

    public function test_publish_rejects_reply_without_threads_id(): void
    {
        $account = ThreadsAccount::factory()->create();
        $reply = Reply::factory()->create([
            'threads_account_id' => $account->id,
            'threads_reply_id' => null,
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(ReplyService::class)->publish($reply, '回應內容');
    }

    public function test_resolve_reply_to_id_returns_threads_reply_id_when_present(): void
    {
        $account = ThreadsAccount::factory()->create();
        $reply = Reply::factory()->create([
            'threads_account_id' => $account->id,
            'threads_reply_id' => 'comment-id-123',
        ]);

        $this->assertSame('comment-id-123', app(ReplyService::class)->resolveReplyToId($reply));
    }

    public function test_resolve_reply_to_id_returns_post_media_id_when_reply_id_null(): void
    {
        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->published()->create(['threads_account_id' => $account->id]);
        $reply = Reply::factory()->create([
            'threads_account_id' => $account->id,
            'post_id' => $post->id,
            'threads_reply_id' => null,
        ]);

        $this->assertSame($post->threads_media_id, app(ReplyService::class)->resolveReplyToId($reply));
    }

    public function test_list_filters_by_status(): void
    {
        Queue::fake();

        $service = app(ReplyService::class);
        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->published()->create(['threads_account_id' => $account->id]);

        $service->createPostReply($account->id, $post->id, 'one');
        $service->createPostReply($account->id, $post->id, 'two');

        $this->assertCount(2, $service->list(['status' => ReplyStatus::New->value]));
    }
}

<?php

namespace Tests\Feature;

use App\Enums\ReplySource;
use App\Enums\ReplyStatus;
use App\Jobs\PublishReply;
use App\Models\Post;
use App\Models\Reply;
use App\Models\ThreadsAccount;
use App\Models\User;
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

        $user = User::factory()->create();
        $this->actingAs($user);

        $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);
        $post = Post::factory()->published()->create(['threads_account_id' => $account->id, 'user_id' => $user->id]);

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
        $user = User::factory()->create();
        $this->actingAs($user);

        $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);
        $post = Post::factory()->create(['threads_account_id' => $account->id, 'threads_media_id' => null, 'user_id' => $user->id]);

        $this->expectException(InvalidArgumentException::class);

        app(ReplyService::class)->createPostReply($account->id, $post->id, '內容');
    }

    public function test_publish_dispatches_job_with_text(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);
        $reply = Reply::factory()->create([
            'user_id' => $user->id,
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
        $user = User::factory()->create();
        $this->actingAs($user);

        $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);
        $reply = Reply::factory()->create([
            'user_id' => $user->id,
            'threads_account_id' => $account->id,
            'threads_reply_id' => null,
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(ReplyService::class)->publish($reply, '回應內容');
    }

    public function test_resolve_reply_to_id_returns_threads_reply_id_when_present(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);
        $reply = Reply::factory()->create([
            'user_id' => $user->id,
            'threads_account_id' => $account->id,
            'threads_reply_id' => 'comment-id-123',
        ]);

        $this->assertSame('comment-id-123', app(ReplyService::class)->resolveReplyToId($reply));
    }

    public function test_resolve_reply_to_id_returns_post_media_id_when_reply_id_null(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);
        $post = Post::factory()->published()->create(['threads_account_id' => $account->id, 'user_id' => $user->id]);
        $reply = Reply::factory()->create([
            'user_id' => $user->id,
            'threads_account_id' => $account->id,
            'post_id' => $post->id,
            'threads_reply_id' => null,
        ]);

        $this->assertSame($post->threads_media_id, app(ReplyService::class)->resolveReplyToId($reply));
    }

    public function test_list_filters_by_status(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        $service = app(ReplyService::class);
        $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);
        $post = Post::factory()->published()->create(['threads_account_id' => $account->id, 'user_id' => $user->id]);

        $service->createPostReply($account->id, $post->id, 'one');
        $service->createPostReply($account->id, $post->id, 'two');

        $this->assertCount(2, $service->list(['status' => ReplyStatus::New->value]));
    }

    public function test_create_post_reply_rejects_foreign_account(): void
    {
        Queue::fake();

        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->actingAs($userA);

        $foreignAccount = ThreadsAccount::factory()->create(['user_id' => $userB->id]);
        $post = Post::factory()->published()->create(['threads_account_id' => $foreignAccount->id, 'user_id' => $userA->id]);

        $this->expectException(InvalidArgumentException::class);

        app(ReplyService::class)->createPostReply($foreignAccount->id, $post->id, '內容');
    }

    public function test_list_only_returns_own_replies(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->actingAs($userA);

        $accountA = ThreadsAccount::factory()->create(['user_id' => $userA->id]);
        $postA = Post::factory()->published()->create(['threads_account_id' => $accountA->id, 'user_id' => $userA->id]);

        $replyOwn = Reply::factory()->create(['threads_account_id' => $accountA->id, 'post_id' => $postA->id, 'user_id' => $userA->id]);
        Reply::factory()->create(['user_id' => $userB->id]);

        $result = app(ReplyService::class)->list();

        $this->assertCount(1, $result);
        $this->assertSame($replyOwn->id, $result->first()->id);
    }

    public function test_unread_count_counts_only_unread_replies(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);
        $post = Post::factory()->published()->create(['threads_account_id' => $account->id, 'user_id' => $user->id]);

        Reply::factory()->create([
            'user_id' => $user->id,
            'threads_account_id' => $account->id,
            'post_id' => $post->id,
            'read_at' => null,
        ]);
        Reply::factory()->create([
            'user_id' => $user->id,
            'threads_account_id' => $account->id,
            'post_id' => $post->id,
            'read_at' => now(),
        ]);

        $this->assertSame(1, app(ReplyService::class)->unreadCount($post->id));
    }

    public function test_mark_as_read_updates_all_replies_of_post(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);
        $post = Post::factory()->published()->create(['threads_account_id' => $account->id, 'user_id' => $user->id]);

        $replyA = Reply::factory()->create([
            'user_id' => $user->id,
            'threads_account_id' => $account->id,
            'post_id' => $post->id,
            'read_at' => null,
        ]);
        $replyB = Reply::factory()->create([
            'user_id' => $user->id,
            'threads_account_id' => $account->id,
            'post_id' => $post->id,
            'read_at' => null,
        ]);

        $updated = app(ReplyService::class)->markAsRead($post->id);

        $this->assertSame(2, $updated);
        $this->assertNotNull($replyA->fresh()->read_at);
        $this->assertNotNull($replyB->fresh()->read_at);
        $this->assertSame(0, app(ReplyService::class)->unreadCount($post->id));
    }

    public function test_mark_as_read_ignores_other_posts(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);
        $postA = Post::factory()->published()->create(['threads_account_id' => $account->id, 'user_id' => $user->id]);
        $postB = Post::factory()->published()->create(['threads_account_id' => $account->id, 'user_id' => $user->id]);

        Reply::factory()->create([
            'user_id' => $user->id,
            'threads_account_id' => $account->id,
            'post_id' => $postA->id,
            'read_at' => null,
        ]);
        $replyOther = Reply::factory()->create([
            'user_id' => $user->id,
            'threads_account_id' => $account->id,
            'post_id' => $postB->id,
            'read_at' => null,
        ]);

        app(ReplyService::class)->markAsRead($postA->id);

        $this->assertNull($replyOther->fresh()->read_at);
        $this->assertSame(1, app(ReplyService::class)->unreadCount($postB->id));
    }
}

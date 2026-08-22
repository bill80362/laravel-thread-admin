<?php

namespace Tests\Feature;

use App\Enums\ReplySource;
use App\Enums\ReplyStatus;
use App\Models\Post;
use App\Models\Reply;
use App\Models\ThreadsAccount;
use App\Services\ThreadsWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThreadsWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_reply_event_creates_reply(): void
    {
        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->published()->create([
            'user_id' => $account->user_id,
            'threads_account_id' => $account->id,
            'threads_media_id' => 'media-1',
        ]);

        $service = app(ThreadsWebhookService::class);
        $service->handleEvent([
            'entry' => [[
                'changes' => [[
                    'field' => 'replies',
                    'value' => [
                        'media_id' => 'media-1',
                        'reply_id' => 'reply-1',
                        'text' => 'hi',
                        'username' => 'user1',
                    ],
                ]],
            ]],
        ]);

        $this->assertDatabaseHas('replies', [
            'threads_reply_id' => 'reply-1',
            'threads_account_id' => $account->id,
            'post_id' => $post->id,
            'user_id' => $account->user_id,
            'source' => ReplySource::Webhook->value,
            'status' => ReplyStatus::New->value,
        ]);
    }

    public function test_reply_event_marks_unread(): void
    {
        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->published()->create([
            'user_id' => $account->user_id,
            'threads_account_id' => $account->id,
            'threads_media_id' => 'media-1',
        ]);

        $service = app(ThreadsWebhookService::class);
        $service->handleEvent([
            'entry' => [[
                'changes' => [[
                    'field' => 'replies',
                    'value' => [
                        'media_id' => 'media-1',
                        'reply_id' => 'reply-unread',
                        'text' => 'hi',
                        'username' => 'user1',
                    ],
                ]],
            ]],
        ]);

        $reply = Reply::query()->where('threads_reply_id', 'reply-unread')->firstOrFail();
        $this->assertNull($reply->read_at);
    }

    public function test_duplicate_reply_event_not_duplicated(): void
    {
        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->published()->create([
            'user_id' => $account->user_id,
            'threads_account_id' => $account->id,
            'threads_media_id' => 'media-1',
        ]);

        Reply::factory()->create([
            'threads_account_id' => $account->id,
            'post_id' => $post->id,
            'threads_reply_id' => 'reply-existing',
        ]);

        $service = app(ThreadsWebhookService::class);
        $service->handleEvent([
            'entry' => [[
                'changes' => [[
                    'field' => 'replies',
                    'value' => [
                        'media_id' => 'media-1',
                        'reply_id' => 'reply-existing',
                        'text' => 'hi',
                        'username' => 'user1',
                    ],
                ]],
            ]],
        ]);

        $this->assertSame(1, Reply::query()->count());
    }

    public function test_unmatched_event_is_skipped(): void
    {
        $service = app(ThreadsWebhookService::class);
        $service->handleEvent([
            'entry' => [[
                'changes' => [[
                    'field' => 'replies',
                    'value' => [
                        'media_id' => 'unknown-media',
                        'reply_id' => 'reply-orphan',
                        'text' => 'hi',
                        'username' => 'user1',
                    ],
                ]],
            ]],
        ]);

        $this->assertSame(0, Reply::query()->count());
    }

    public function test_verification_returns_challenge(): void
    {
        config(['services.threads.webhook_verify_token' => 'secret-token']);

        $response = $this->get('/threads/webhook?hub.mode=subscribe&hub.verify_token=secret-token&hub.challenge=abc123');

        $response->assertOk();
        $response->assertContent('abc123');
    }

    public function test_verification_rejects_wrong_token(): void
    {
        config(['services.threads.webhook_verify_token' => 'secret-token']);

        $response = $this->get('/threads/webhook?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=abc123');

        $response->assertForbidden();
    }

    public function test_post_event_creates_reply(): void
    {
        config(['services.threads.webhook_verify_token' => 'secret-token']);

        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->published()->create([
            'user_id' => $account->user_id,
            'threads_account_id' => $account->id,
            'threads_media_id' => 'media-1',
        ]);

        $response = $this->postJson('/threads/webhook', [
            'entry' => [[
                'changes' => [[
                    'field' => 'replies',
                    'value' => [
                        'media_id' => 'media-1',
                        'reply_id' => 'reply-http',
                        'text' => 'hi',
                        'username' => 'user1',
                    ],
                ]],
            ]],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('replies', ['threads_reply_id' => 'reply-http']);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Enums\ThreadsAccountStatus;
use App\Jobs\DeletePost;
use App\Models\Post;
use App\Models\Reply;
use App\Models\ThreadsAccount;
use App\Models\User;
use App\Services\PostService;
use App\Services\ThreadsClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class DeletePostTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ThreadsAccount $account;

    private PostService $postService;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.threads.client_id', 'test-client-id');
        Config::set('services.threads.client_secret', 'test-client-secret');

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->account = ThreadsAccount::factory()->create(['user_id' => $this->user->id]);
        $this->postService = app(PostService::class);
    }

    public function test_delete_published_post_dispatches_job_and_sets_deleting(): void
    {
        Queue::fake();

        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'threads_account_id' => $this->account->id,
            'status' => PostStatus::Published,
            'threads_media_id' => 'test-media-id',
        ]);

        $this->postService->delete($post->id);

        $post->refresh();
        $this->assertSame(PostStatus::Deleting, $post->status);

        Queue::assertPushed(DeletePost::class, fn ($job) => $job->postId === $post->id);
    }

    public function test_delete_delete_failed_post_dispatches_job_again(): void
    {
        Queue::fake();

        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'threads_account_id' => $this->account->id,
            'status' => PostStatus::DeleteFailed,
            'threads_media_id' => 'test-media-id',
            'error_message' => 'previous error',
        ]);

        $this->postService->delete($post->id);

        $post->refresh();
        $this->assertSame(PostStatus::Deleting, $post->status);

        Queue::assertPushed(DeletePost::class);
    }

    public function test_delete_draft_post_deletes_immediately(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'threads_account_id' => $this->account->id,
            'status' => PostStatus::Draft,
        ]);

        $this->postService->delete($post->id);

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_delete_job_success_removes_post_and_cascade_replies(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'threads_account_id' => $this->account->id,
            'status' => PostStatus::Deleting,
            'threads_media_id' => 'test-media-id',
        ]);

        $reply = Reply::factory()->create([
            'user_id' => $this->user->id,
            'threads_account_id' => $this->account->id,
            'post_id' => $post->id,
        ]);

        $http = Mockery::mock(ClientInterface::class);
        $http->shouldReceive('request')
            ->once()
            ->andReturn(new Response(200, [], json_encode(['success' => true])));

        $client = new ThreadsClient($http);
        $job = new DeletePost($post->id);
        $job->handle($client);

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertDatabaseMissing('replies', ['id' => $reply->id]);
    }

    public function test_delete_job_failure_sets_delete_failed_and_preserves_record(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'threads_account_id' => $this->account->id,
            'status' => PostStatus::Deleting,
            'threads_media_id' => 'test-media-id',
        ]);

        $http = Mockery::mock(ClientInterface::class);
        $http->shouldReceive('request')
            ->once()
            ->andReturn(new Response(400, [], json_encode([
                'error' => ['message' => 'Some error', 'code' => 100],
            ])));

        $client = new ThreadsClient($http);
        $job = new DeletePost($post->id);
        $job->handle($client);

        $post->refresh();
        $this->assertSame(PostStatus::DeleteFailed, $post->status);
        $this->assertNotNull($post->error_message);
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public function test_delete_job_token_invalid_sets_account_needs_reauth(): void
    {
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'threads_account_id' => $this->account->id,
            'status' => PostStatus::Deleting,
            'threads_media_id' => 'test-media-id',
        ]);

        $request = new Request('DELETE', 'https://graph.threads.net/v1.0/test-media-id');
        $response = new Response(401, [], json_encode([
            'error' => ['message' => 'Invalid OAuth access token', 'code' => 190],
        ]));

        $http = Mockery::mock(ClientInterface::class);
        $http->shouldReceive('request')
            ->once()
            ->andThrow(new ClientException('Client error', $request, $response));

        $client = new ThreadsClient($http);
        $job = new DeletePost($post->id);
        $job->handle($client);

        $post->refresh();
        $this->assertSame(PostStatus::DeleteFailed, $post->status);

        $this->account->refresh();
        $this->assertSame(ThreadsAccountStatus::NeedsReauth, $this->account->status);
    }

    public function test_cannot_delete_other_users_post(): void
    {
        $otherUser = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $otherUser->id,
            'threads_account_id' => $this->account->id,
            'status' => PostStatus::Published,
            'threads_media_id' => 'test-media-id',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->postService->delete($post->id);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

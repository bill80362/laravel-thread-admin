<?php

namespace Tests\Feature;

use App\Jobs\PublishReply;
use App\Mcp\Servers\ThreadsMcpServer;
use App\Mcp\Tools\CreatePostTool;
use App\Mcp\Tools\CreateReplyTool;
use App\Mcp\Tools\GetPostTool;
use App\Mcp\Tools\ListAccountsTool;
use App\Mcp\Tools\ListPostsTool;
use App\Mcp\Tools\ListRepliesTool;
use App\Models\Post;
use App\Models\Reply;
use App\Models\ThreadsAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class McpToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_accounts_returns_accounts(): void
    {
        $user = User::factory()->create();
        ThreadsAccount::factory()->create(['username' => 'alice', 'user_id' => $user->id]);

        ThreadsMcpServer::actingAs($user)
            ->tool(ListAccountsTool::class)
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('accounts.0.username', 'alice')
                ->where('accounts.0.status', 'active')
                ->etc());
    }

    public function test_list_accounts_filters_by_status(): void
    {
        $user = User::factory()->create();
        ThreadsAccount::factory()->create(['user_id' => $user->id]);
        ThreadsAccount::factory()->needsReauth()->create(['user_id' => $user->id]);

        ThreadsMcpServer::actingAs($user)
            ->tool(ListAccountsTool::class, ['status' => 'needs_reauth'])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('accounts', fn ($accounts) => count($accounts) === 1)
                ->etc());
    }

    public function test_create_post_requires_fields(): void
    {
        $user = User::factory()->create();

        ThreadsMcpServer::actingAs($user)
            ->tool(CreatePostTool::class, [])
            ->assertHasErrors();
    }

    public function test_create_post_creates_scheduled_post(): void
    {
        $user = User::factory()->create();
        $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);

        ThreadsMcpServer::actingAs($user)
            ->tool(CreatePostTool::class, [
                'threads_account_id' => $account->id,
                'text' => '來自 MCP 的貼文',
                'scheduled_at' => now()->addHour()->toIso8601String(),
            ])->assertOk();

        $this->assertDatabaseHas('posts', [
            'threads_account_id' => $account->id,
            'text' => '來自 MCP 的貼文',
        ]);
    }

    public function test_list_posts_returns_posts(): void
    {
        $user = User::factory()->create();
        Post::factory()->create(['text' => '第一篇', 'user_id' => $user->id]);

        ThreadsMcpServer::actingAs($user)
            ->tool(ListPostsTool::class)
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('posts', fn ($posts) => count($posts) === 1)
                ->etc());
    }

    public function test_get_post_returns_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['text' => '查詢我', 'user_id' => $user->id]);

        ThreadsMcpServer::actingAs($user)
            ->tool(GetPostTool::class, ['post_id' => $post->id])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('post.text', '查詢我')
                ->etc());
    }

    public function test_get_post_returns_error_when_missing(): void
    {
        $user = User::factory()->create();

        ThreadsMcpServer::actingAs($user)
            ->tool(GetPostTool::class, ['post_id' => 999999])
            ->assertHasErrors();
    }

    public function test_list_replies_returns_replies(): void
    {
        $user = User::factory()->create();
        Reply::factory()->create(['text' => '一則回覆', 'user_id' => $user->id]);

        ThreadsMcpServer::actingAs($user)
            ->tool(ListRepliesTool::class)
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('replies', fn ($replies) => count($replies) === 1)
                ->etc());
    }

    public function test_create_reply_requires_post(): void
    {
        $user = User::factory()->create();
        $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);

        ThreadsMcpServer::actingAs($user)
            ->tool(CreateReplyTool::class, [
                'threads_account_id' => $account->id,
                'text' => '缺少貼文的回覆',
            ])->assertHasErrors();
    }

    public function test_create_reply_creates_post_reply(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);
        $post = Post::factory()->published()->create(['threads_account_id' => $account->id, 'user_id' => $user->id]);

        ThreadsMcpServer::actingAs($user)
            ->tool(CreateReplyTool::class, [
                'threads_account_id' => $account->id,
                'post_id' => $post->id,
                'text' => '來自 MCP 的回覆',
            ])->assertOk();

        $this->assertDatabaseHas('replies', [
            'threads_account_id' => $account->id,
            'post_id' => $post->id,
            'text' => '來自 MCP 的回覆',
            'source' => 'manual',
            'status' => 'new',
        ]);

        Queue::assertPushed(PublishReply::class, 1);
    }

    public function test_list_accounts_returns_only_own_accounts(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        ThreadsAccount::factory()->create(['username' => 'alice', 'user_id' => $userA->id]);
        ThreadsAccount::factory()->create(['username' => 'bob', 'user_id' => $userB->id]);

        ThreadsMcpServer::actingAs($userA)
            ->tool(ListAccountsTool::class)
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('accounts', fn ($accounts) => count($accounts) === 1)
                ->where('accounts.0.username', 'alice')
                ->etc());
    }

    public function test_create_post_rejects_foreign_account(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $foreignAccount = ThreadsAccount::factory()->create(['user_id' => $userB->id]);

        ThreadsMcpServer::actingAs($userA)
            ->tool(CreatePostTool::class, [
                'threads_account_id' => $foreignAccount->id,
                'text' => '越權貼文',
                'scheduled_at' => now()->addHour()->toIso8601String(),
            ])->assertHasErrors();
    }
}

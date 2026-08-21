<?php

namespace Tests\Feature;

use App\Enums\ReplySource;
use App\Jobs\PublishReply;
use App\Livewire\PostReplyDrawer;
use App\Models\Post;
use App\Models\Reply;
use App\Models\ThreadsAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class PostReplyDrawerTest extends TestCase
{
    use RefreshDatabase;

    private function setUpWorld(User $user): array
    {
        $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);
        $post = Post::factory()->published()->create([
            'user_id' => $user->id,
            'threads_account_id' => $account->id,
        ]);

        return [$account, $post];
    }

    public function test_mount_marks_replies_as_read(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        [$account, $post] = $this->setUpWorld($user);

        $reply = Reply::factory()->create([
            'user_id' => $user->id,
            'threads_account_id' => $account->id,
            'post_id' => $post->id,
            'read_at' => null,
        ]);

        Livewire::test(PostReplyDrawer::class, ['postId' => $post->id]);

        $this->assertNotNull($reply->fresh()->read_at);
    }

    public function test_mount_lists_replies_oldest_first(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        [$account, $post] = $this->setUpWorld($user);

        Reply::factory()->create([
            'user_id' => $user->id,
            'threads_account_id' => $account->id,
            'post_id' => $post->id,
            'created_at' => now()->subMinutes(10),
            'text' => '較舊的回覆',
        ]);
        Reply::factory()->create([
            'user_id' => $user->id,
            'threads_account_id' => $account->id,
            'post_id' => $post->id,
            'created_at' => now(),
            'text' => '最新的回覆',
        ]);

        $component = Livewire::test(PostReplyDrawer::class, ['postId' => $post->id]);

        $component->assertSeeInOrder(['較舊的回覆', '最新的回覆']);
    }

    public function test_send_reply_creates_reply_and_dispatches_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        [$account, $post] = $this->setUpWorld($user);

        Livewire::test(PostReplyDrawer::class, ['postId' => $post->id])
            ->set('replyText', '回覆貼文內容')
            ->call('sendReply')
            ->assertHasNoErrors()
            ->assertSet('replyText', '');

        $this->assertDatabaseHas('replies', [
            'user_id' => $user->id,
            'threads_account_id' => $account->id,
            'post_id' => $post->id,
            'text' => '回覆貼文內容',
            'source' => ReplySource::Manual->value,
        ]);

        Queue::assertPushed(PublishReply::class);
    }

    public function test_send_reply_validates_empty_text(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        [$account, $post] = $this->setUpWorld($user);

        Livewire::test(PostReplyDrawer::class, ['postId' => $post->id])
            ->set('replyText', '')
            ->call('sendReply')
            ->assertHasErrors(['replyText' => 'required']);
    }
}

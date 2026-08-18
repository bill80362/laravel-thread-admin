<?php

namespace Tests\Feature;

use App\Enums\ReplySource;
use App\Enums\ReplyStatus;
use App\Filament\Resources\Replies\Pages\CreateReply;
use App\Filament\Resources\Replies\Pages\ListReplies;
use App\Jobs\PublishReply;
use App\Models\Post;
use App\Models\Reply;
use App\Models\ThreadsAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class ReplyResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_reply_with_valid_data(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);
        $post = Post::factory()->published()->create(['threads_account_id' => $account->id, 'user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(CreateReply::class)
            ->fillForm([
                'threads_account_id' => $account->id,
                'post_id' => $post->id,
                'text' => '這是一則測試回覆',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('replies', [
            'threads_account_id' => $account->id,
            'text' => '這是一則測試回覆',
            'source' => ReplySource::Manual->value,
            'status' => ReplyStatus::New->value,
            'post_id' => $post->id,
        ]);
    }

    public function test_create_reply_rejects_missing_required_fields(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateReply::class)
            ->fillForm([
                'threads_account_id' => null,
                'post_id' => null,
                'text' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'threads_account_id' => 'required',
                'post_id' => 'required',
                'text' => 'required',
            ]);
    }

    public function test_list_replies_shows_records(): void
    {
        $user = User::factory()->create();
        $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);
        $replies = Reply::factory()->count(3)->create([
            'user_id' => $user->id,
            'threads_account_id' => $account->id,
        ]);

        Livewire::actingAs($user)
            ->test(ListReplies::class)
            ->assertCanSeeTableRecords($replies);
    }

    public function test_reply_action_dispatches_publish_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $account = ThreadsAccount::factory()->create(['user_id' => $user->id]);
        $reply = Reply::factory()->create([
            'user_id' => $user->id,
            'threads_account_id' => $account->id,
            'threads_reply_id' => '12345',
            'status' => ReplyStatus::New,
        ]);

        Livewire::actingAs($user)
            ->test(ListReplies::class)
            ->callTableAction('reply', $reply, ['text' => '回應內容'])
            ->assertNotified();

        Queue::assertPushed(PublishReply::class, function ($job) use ($reply) {
            return $job->replyId === $reply->id && $job->replyText === '回應內容';
        });
    }
}

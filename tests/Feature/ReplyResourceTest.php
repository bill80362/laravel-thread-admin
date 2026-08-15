<?php

namespace Tests\Feature;

use App\Enums\ReplySource;
use App\Enums\ReplyStatus;
use App\Filament\Resources\Replies\Pages\CreateReply;
use App\Filament\Resources\Replies\Pages\ListReplies;
use App\Models\Post;
use App\Models\Reply;
use App\Models\ThreadsAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReplyResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_reply_with_valid_data(): void
    {
        $account = ThreadsAccount::factory()->create();

        Livewire::actingAs(User::factory()->create())
            ->test(CreateReply::class)
            ->fillForm([
                'threads_account_id' => $account->id,
                'author_username' => 'someuser',
                'text' => '這是一則測試回覆',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('replies', [
            'threads_account_id' => $account->id,
            'author_username' => 'someuser',
            'text' => '這是一則測試回覆',
            'source' => ReplySource::Manual->value,
            'status' => ReplyStatus::New->value,
            'post_id' => null,
        ]);
    }

    public function test_create_reply_rejects_missing_required_fields(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(CreateReply::class)
            ->fillForm([
                'threads_account_id' => null,
                'author_username' => null,
                'text' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'threads_account_id' => 'required',
                'author_username' => 'required',
                'text' => 'required',
            ]);
    }

    public function test_create_reply_rejects_text_over_500_chars(): void
    {
        $account = ThreadsAccount::factory()->create();

        Livewire::actingAs(User::factory()->create())
            ->test(CreateReply::class)
            ->fillForm([
                'threads_account_id' => $account->id,
                'author_username' => 'someuser',
                'text' => str_repeat('a', 501),
            ])
            ->call('create')
            ->assertHasFormErrors(['text' => 'max']);
    }

    public function test_create_reply_with_optional_post(): void
    {
        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->create([
            'threads_account_id' => $account->id,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(CreateReply::class)
            ->fillForm([
                'threads_account_id' => $account->id,
                'post_id' => $post->id,
                'author_username' => 'someuser',
                'text' => '關聯貼文的回覆',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('replies', [
            'post_id' => $post->id,
            'text' => '關聯貼文的回覆',
        ]);
    }

    public function test_list_replies_shows_records(): void
    {
        $account = ThreadsAccount::factory()->create();
        $replies = Reply::factory()->count(3)->create([
            'threads_account_id' => $account->id,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListReplies::class)
            ->assertCanSeeTableRecords($replies);
    }
}

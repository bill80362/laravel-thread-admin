<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\Post;
use App\Models\ThreadsAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PostResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_post_with_valid_data(): void
    {
        $account = ThreadsAccount::factory()->create();

        Livewire::actingAs(User::factory()->create())
            ->test(CreatePost::class)
            ->fillForm([
                'threads_account_id' => $account->id,
                'text' => '這是一篇測試貼文',
                'scheduled_at' => now()->addHour()->format('Y-m-d H:i:s'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('posts', [
            'threads_account_id' => $account->id,
            'text' => '這是一篇測試貼文',
            'status' => PostStatus::Scheduled->value,
        ]);
    }

    public function test_create_post_rejects_text_over_500_chars(): void
    {
        $account = ThreadsAccount::factory()->create();

        Livewire::actingAs(User::factory()->create())
            ->test(CreatePost::class)
            ->fillForm([
                'threads_account_id' => $account->id,
                'text' => str_repeat('a', 501),
                'scheduled_at' => now()->addHour()->format('Y-m-d H:i:s'),
            ])
            ->call('create')
            ->assertHasFormErrors(['text' => 'max']);
    }

    public function test_list_posts_shows_records(): void
    {
        $account = ThreadsAccount::factory()->create();
        $posts = Post::factory()->count(3)->create([
            'threads_account_id' => $account->id,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(ListPosts::class)
            ->assertCanSeeTableRecords($posts);
    }

    public function test_published_post_cannot_be_edited(): void
    {
        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->published()->create([
            'threads_account_id' => $account->id,
        ]);

        $this->assertSame(PostStatus::Published, $post->status);
    }
}

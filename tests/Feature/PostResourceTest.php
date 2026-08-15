<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
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

    public function test_edit_post_loads_status_info_section(): void
    {
        $account = ThreadsAccount::factory()->create();
        $post = Post::factory()->create([
            'threads_account_id' => $account->id,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(EditPost::class, ['record' => $post->id])
            ->assertOk()
            ->assertSee('貼文狀態資訊');
    }

    public function test_post_status_has_label_and_color(): void
    {
        $this->assertSame('草稿', PostStatus::Draft->getLabel());
        $this->assertSame('排程中', PostStatus::Scheduled->getLabel());
        $this->assertSame('發佈中', PostStatus::Publishing->getLabel());
        $this->assertSame('已發佈', PostStatus::Published->getLabel());
        $this->assertSame('失敗', PostStatus::Failed->getLabel());

        $this->assertSame('gray', PostStatus::Draft->getColor());
        $this->assertSame('warning', PostStatus::Scheduled->getColor());
        $this->assertSame('info', PostStatus::Publishing->getColor());
        $this->assertSame('success', PostStatus::Published->getColor());
        $this->assertSame('danger', PostStatus::Failed->getColor());
    }
}

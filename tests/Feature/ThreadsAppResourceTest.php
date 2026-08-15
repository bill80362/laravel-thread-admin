<?php

namespace Tests\Feature;

use App\Filament\Resources\ThreadsApps\Pages\CreateThreadsApp;
use App\Filament\Resources\ThreadsApps\Pages\EditThreadsApp;
use App\Filament\Resources\ThreadsApps\Pages\ListThreadsApps;
use App\Models\ThreadsApp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ThreadsAppResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_threads_app(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateThreadsApp::class)
            ->fillForm([
                'name' => '測試 App',
                'client_id' => 'test-client-id',
                'client_secret' => 'test-client-secret',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('threads_apps', [
            'name' => '測試 App',
            'client_id' => 'test-client-id',
            'user_id' => $user->id,
        ]);
    }

    public function test_list_threads_apps_shows_own_apps_only(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $appA = ThreadsApp::factory()->create(['user_id' => $userA->id, 'name' => 'App A']);
        $appB = ThreadsApp::factory()->create(['user_id' => $userB->id, 'name' => 'App B']);

        Livewire::actingAs($userA)
            ->test(ListThreadsApps::class)
            ->assertCanSeeTableRecords([$appA])
            ->assertCanNotSeeTableRecords([$appB]);
    }

    public function test_edit_threads_app(): void
    {
        $user = User::factory()->create();
        $app = ThreadsApp::factory()->create(['user_id' => $user->id, 'name' => '舊名稱']);

        Livewire::actingAs($user)
            ->test(EditThreadsApp::class, ['record' => $app->id])
            ->fillForm(['name' => '新名稱'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('threads_apps', [
            'id' => $app->id,
            'name' => '新名稱',
        ]);
    }
}

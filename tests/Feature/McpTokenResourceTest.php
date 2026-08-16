<?php

namespace Tests\Feature;

use App\Filament\Resources\McpTokens\Pages\ListMcpTokens;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Client;
use Laravel\Passport\Token;
use Livewire\Livewire;
use Tests\TestCase;

class McpTokenResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_mcp_tokens_shows_own_tokens(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $client = Client::factory()->create(['name' => 'Claude Desktop']);

        $myToken = Token::forceCreate([
            'id' => 'abc123def456',
            'user_id' => $user->id,
            'client_id' => $client->id,
            'name' => null,
            'scopes' => ['mcp:use'],
            'revoked' => false,
            'expires_at' => now()->addDays(30),
        ]);

        $otherToken = Token::forceCreate([
            'id' => 'xyz789ghi012',
            'user_id' => $otherUser->id,
            'client_id' => $client->id,
            'name' => null,
            'scopes' => ['mcp:use'],
            'revoked' => false,
            'expires_at' => now()->addDays(30),
        ]);

        Livewire::actingAs($user)
            ->test(ListMcpTokens::class)
            ->assertCanSeeTableRecords([$myToken]);
    }

    public function test_list_mcp_tokens_shows_empty_when_no_tokens(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ListMcpTokens::class)
            ->assertOk();
    }

    public function test_revoke_token(): void
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['name' => 'Claude Desktop']);

        $token = Token::forceCreate([
            'id' => 'revoke-me-token',
            'user_id' => $user->id,
            'client_id' => $client->id,
            'name' => null,
            'scopes' => ['mcp:use'],
            'revoked' => false,
            'expires_at' => now()->addDays(30),
        ]);

        Livewire::actingAs($user)
            ->test(ListMcpTokens::class)
            ->callAction(TestAction::make('revoke')->table($token));

        $this->assertTrue($token->fresh()->revoked);
    }

    public function test_cannot_see_other_user_tokens(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $client = Client::factory()->create(['name' => 'ChatGPT']);

        $otherToken = Token::forceCreate([
            'id' => 'other-user-token',
            'user_id' => $otherUser->id,
            'client_id' => $client->id,
            'name' => null,
            'scopes' => ['mcp:use'],
            'revoked' => false,
            'expires_at' => now()->addDays(30),
        ]);

        Livewire::actingAs($user)
            ->test(ListMcpTokens::class)
            ->assertCanNotSeeTableRecords([$otherToken]);
    }
}

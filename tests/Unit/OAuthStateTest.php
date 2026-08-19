<?php

namespace Tests\Unit;

use App\Models\OAuthState;
use App\Models\ThreadsAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OAuthStateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_create_for_user_stores_hash_and_returns_raw_token(): void
    {
        $token = OAuthState::createForUser();

        $this->assertIsString($token);
        $this->assertNotSame('', $token);
        $this->assertDatabaseMissing('threads_oauth_states', ['token_hash' => $token]);

        $this->assertDatabaseHas('threads_oauth_states', [
            'token_hash' => hash('sha256', $token),
            'user_id' => $this->user->id,
        ]);
    }

    public function test_resolve_returns_user_id_and_consumes_state(): void
    {
        $token = OAuthState::createForUser();

        $resolved = OAuthState::resolve($token);

        $this->assertNotNull($resolved);
        $this->assertSame($this->user->id, $resolved['user_id']);
        $this->assertNull($resolved['account']);

        // 單次使用：解析後即刪除。
        $this->assertNull(OAuthState::resolve($token));
    }

    public function test_resolve_with_target_account_returns_account(): void
    {
        $account = ThreadsAccount::factory()->create(['user_id' => $this->user->id]);
        $token = OAuthState::createForUser($account);

        $resolved = OAuthState::resolve($token);

        $this->assertNotNull($resolved);
        $this->assertSame($account->id, $resolved['account']->id);
    }

    public function test_resolve_invalid_token_returns_null(): void
    {
        $this->assertNull(OAuthState::resolve('nonexistent-token'));
    }

    public function test_resolve_expired_token_returns_null(): void
    {
        $token = OAuthState::createForUser();

        // 手動將過期時間改成過去。
        OAuthState::query()->update(['expires_at' => now()->subMinute()]);

        $this->assertNull(OAuthState::resolve($token));
    }
}

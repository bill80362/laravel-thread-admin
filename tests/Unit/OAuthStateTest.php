<?php

namespace Tests\Unit;

use App\Models\OAuthState;
use App\Models\ThreadsAccount;
use App\Models\ThreadsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OAuthStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_for_app_stores_hash_and_returns_raw_token(): void
    {
        $app = ThreadsApp::factory()->create();

        $token = OAuthState::createForApp($app);

        $this->assertIsString($token);
        $this->assertNotSame('', $token);
        $this->assertDatabaseMissing('threads_oauth_states', ['token_hash' => $token]);

        $this->assertDatabaseHas('threads_oauth_states', [
            'token_hash' => hash('sha256', $token),
            'threads_app_id' => $app->id,
        ]);
    }

    public function test_resolve_returns_app_and_consumes_state(): void
    {
        $app = ThreadsApp::factory()->create();
        $token = OAuthState::createForApp($app);

        $resolved = OAuthState::resolve($token);

        $this->assertNotNull($resolved);
        $this->assertSame($app->id, $resolved['app']->id);
        $this->assertNull($resolved['account']);

        // 單次使用：解析後即刪除。
        $this->assertNull(OAuthState::resolve($token));
    }

    public function test_resolve_with_target_account_returns_account(): void
    {
        $app = ThreadsApp::factory()->create();
        $account = ThreadsAccount::factory()->create(['threads_app_id' => $app->id]);
        $token = OAuthState::createForApp($app, $account);

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
        $app = ThreadsApp::factory()->create();
        $token = OAuthState::createForApp($app);

        // 手動將過期時間改成過去。
        OAuthState::query()->update(['expires_at' => now()->subMinute()]);

        $this->assertNull(OAuthState::resolve($token));
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\ThreadsAccountStatus;
use App\Models\ThreadsAccount;
use App\Services\ThreadsClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class ThreadsOAuthController extends Controller
{
    public function __construct(private readonly ThreadsClient $threads) {}

    /**
     * Redirect the user to the Threads authorization window.
     */
    public function redirect(): RedirectResponse
    {
        $state = bin2hex(random_bytes(16));

        session(['threads_oauth_state' => $state]);

        return redirect()->away($this->threads->buildAuthorizationUrl($state));
    }

    /**
     * Handle the OAuth callback, exchange tokens, and store the account.
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            return $this->fail('授權已取消');
        }

        $state = $request->query('state');
        $expected = session()->pull('threads_oauth_state');

        if ($state === null || $state !== $expected) {
            return $this->fail('OAuth state 不符，請重新授權');
        }

        $code = $request->query('code');

        if ($code === null) {
            return $this->fail('缺少授權碼，請重新授權');
        }

        try {
            $shortToken = $this->threads->exchangeCodeForShortToken($code);
            $longToken = $this->threads->exchangeShortForLongToken($shortToken);
            $profile = $this->threads->getProfile($longToken['access_token']);

            $account = ThreadsAccount::query()->updateOrCreate(
                ['threads_user_id' => $profile['id']],
                [
                    'username' => $profile['username'] ?? $profile['id'],
                    'name' => $profile['name'] ?? null,
                    'avatar' => null,
                    'access_token' => $longToken['access_token'],
                    'token_expires_at' => now()->addSeconds($longToken['expires_in'] ?? 5184000),
                    'status' => ThreadsAccountStatus::Active,
                ],
            );

            return redirect()
                ->to(URL::route('filament.admin.resources.threads-accounts.index'))
                ->with('success', "已成功綁定帳號 @{$account->username}");
        } catch (\Throwable $e) {
            Log::error('Threads OAuth 綁定失敗', ['exception' => $e]);

            return $this->fail('綁定失敗，請重新授權');
        }
    }

    private function fail(string $message): RedirectResponse
    {
        return redirect()
            ->to(URL::route('filament.admin.resources.threads-accounts.index'))
            ->with('error', $message);
    }
}

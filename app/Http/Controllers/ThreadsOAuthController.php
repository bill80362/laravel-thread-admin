<?php

namespace App\Http\Controllers;

use App\Enums\ThreadsAccountStatus;
use App\Models\OAuthState;
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
    public function redirect(Request $request): RedirectResponse
    {
        $targetAccount = null;

        if ($accountId = $request->query('account')) {
            $targetAccount = ThreadsAccount::query()
                ->where('id', $accountId)
                ->where('user_id', auth()->id())
                ->first();
        }

        $state = OAuthState::createForUser($targetAccount);

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

        $rawState = $request->query('state');

        if ($rawState === null) {
            return $this->fail('缺少 OAuth state，請重新授權');
        }

        $resolved = OAuthState::resolve($rawState);

        if ($resolved === null) {
            return $this->fail('OAuth state 無效或已過期，請重新授權');
        }

        $userId = $resolved['user_id'];
        $targetAccount = $resolved['account'];

        $code = $request->query('code');

        if ($code === null) {
            return $this->fail('缺少授權碼，請重新授權');
        }

        try {
            $shortToken = $this->threads->exchangeCodeForShortToken($code);
            $longToken = $this->threads->exchangeShortForLongToken($shortToken);
            $profile = $this->threads->getProfile($longToken['access_token']);

            $attributes = [
                'user_id' => $userId,
                'username' => $profile['username'] ?? $profile['id'],
                'name' => $profile['name'] ?? null,
                'avatar' => null,
                'access_token' => $longToken['access_token'],
                'token_expires_at' => now()->addSeconds($longToken['expires_in'] ?? 5184000),
                'status' => ThreadsAccountStatus::Active,
            ];

            // 重新授權：更新既有帳號；新綁定：updateOrCreate。
            if ($targetAccount !== null) {
                $targetAccount->update($attributes);
                $account = $targetAccount;
                $message = "已重新授權帳號 @{$account->username}";
            } else {
                $account = ThreadsAccount::query()->updateOrCreate(
                    [
                        'threads_user_id' => $profile['id'],
                        'user_id' => $userId,
                    ],
                    $attributes,
                );
                $message = "已成功綁定帳號 @{$account->username}";
            }

            return redirect()
                ->to(URL::route('filament.user.resources.threads-accounts.index'))
                ->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Threads OAuth 綁定失敗', ['exception' => $e]);

            return $this->fail('綁定失敗，請重新授權');
        }
    }

    private function fail(string $message): RedirectResponse
    {
        return redirect()
            ->to(URL::route('filament.user.resources.threads-accounts.index'))
            ->with('error', $message);
    }
}

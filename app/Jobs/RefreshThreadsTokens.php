<?php

namespace App\Jobs;

use App\Enums\ThreadsAccountStatus;
use App\Models\ThreadsAccount;
use App\Services\ThreadsClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RefreshThreadsTokens implements ShouldQueue
{
    use Queueable;

    /**
     * The number of days before expiry at which a token is refreshed.
     */
    public const REFRESH_THRESHOLD_DAYS = 7;

    public function __construct(private readonly ThreadsClient $threads) {}

    /**
     * Refresh long-lived tokens that are within the refresh threshold.
     */
    public function handle(): void
    {
        $accounts = ThreadsAccount::query()
            ->where('status', ThreadsAccountStatus::Active)
            ->where('token_expires_at', '<=', now()->addDays(self::REFRESH_THRESHOLD_DAYS))
            ->get();

        foreach ($accounts as $account) {
            try {
                $result = $this->threads->refreshLongLivedToken($account->access_token);

                $account->update([
                    'access_token' => $result['access_token'],
                    'token_expires_at' => now()->addSeconds($result['expires_in'] ?? 5184000),
                    'status' => ThreadsAccountStatus::Active,
                ]);
            } catch (\Throwable $e) {
                $account->update(['status' => ThreadsAccountStatus::NeedsReauth]);

                Log::warning('Threads token refresh failed', [
                    'account_id' => $account->id,
                    'username' => $account->username,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}

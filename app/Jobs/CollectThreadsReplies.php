<?php

namespace App\Jobs;

use App\Enums\ReplySource;
use App\Enums\ReplyStatus;
use App\Enums\ThreadsAccountStatus;
use App\Exceptions\ThreadsApiException;
use App\Models\Reply;
use App\Models\ThreadsAccount;
use App\Services\ThreadsClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CollectThreadsReplies implements ShouldQueue
{
    use Queueable;

    /**
     * Minimum interval (in minutes) between syncs per account.
     */
    public const SYNC_INTERVAL_MINUTES = 2;

    public function __construct() {}

    /**
     * Poll replies for every active account's published posts.
     */
    public function handle(ThreadsClient $threads): void
    {
        $accounts = ThreadsAccount::query()
            ->where('status', ThreadsAccountStatus::Active)
            ->where(function ($query) {
                $query->whereNull('last_synced_at')
                    ->orWhere('last_synced_at', '<=', now()->subMinutes(self::SYNC_INTERVAL_MINUTES));
            })
            ->get();

        foreach ($accounts as $account) {
            $this->collectForAccount($account, $threads);
        }
    }

    private function collectForAccount(ThreadsAccount $account, ThreadsClient $threads): void
    {
        $publishedPosts = $account->posts()
            ->whereNotNull('threads_media_id')
            ->get();

        try {
            foreach ($publishedPosts as $post) {
                $replies = $threads->getReplies($account, $post->threads_media_id);

                foreach ($replies as $reply) {
                    Reply::query()->firstOrCreate(
                        ['threads_reply_id' => $reply['id']],
                        [
                            'user_id' => $account->user_id,
                            'threads_account_id' => $account->id,
                            'post_id' => $post->id,
                            'author_username' => $reply['username'] ?? '',
                            'text' => $reply['text'] ?? '',
                            'source' => ReplySource::Polling,
                            'status' => ReplyStatus::New,
                        ],
                    );
                }
            }

            $account->update(['last_synced_at' => now()]);
        } catch (ThreadsApiException $e) {
            if ($e->isTokenInvalid()) {
                $account->update(['status' => ThreadsAccountStatus::NeedsReauth]);
            }

            Log::warning('Threads reply collection failed', [
                'account_id' => $account->id,
                'username' => $account->username,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

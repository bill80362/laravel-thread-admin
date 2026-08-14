<?php

namespace App\Console\Commands;

use App\Enums\PostStatus;
use App\Jobs\CollectThreadsReplies;
use App\Jobs\PublishScheduledPost;
use App\Jobs\RefreshThreadsTokens;
use App\Models\Post;
use Illuminate\Console\Command;

class RunThreadsScheduler extends Command
{
    protected $signature = 'threads:schedule';

    protected $description = 'Run all scheduled Threads operations (publish, collect replies, refresh tokens)';

    /**
     * Dispatch due posts, reply collection, and token refresh jobs.
     */
    public function handle(): int
    {
        $this->dispatchDuePosts();
        $this->dispatchReplyCollection();
        $this->dispatchTokenRefresh();

        return self::SUCCESS;
    }

    private function dispatchDuePosts(): void
    {
        $duePosts = Post::query()
            ->where('status', PostStatus::Scheduled)
            ->where('scheduled_at', '<=', now())
            ->pluck('id');

        foreach ($duePosts as $postId) {
            PublishScheduledPost::dispatch($postId);
        }
    }

    private function dispatchReplyCollection(): void
    {
        CollectThreadsReplies::dispatch();
    }

    private function dispatchTokenRefresh(): void
    {
        if (now()->hour === 0) {
            RefreshThreadsTokens::dispatch();
        }
    }
}

<?php

namespace App\Jobs;

use App\Enums\PostStatus;
use App\Enums\ThreadsAccountStatus;
use App\Exceptions\ThreadsApiException;
use App\Models\Post;
use App\Services\ThreadsClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DeletePost implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $postId,
    ) {}

    /**
     * Execute the post deletion flow.
     */
    public function handle(ThreadsClient $threads): void
    {
        $post = Post::query()->find($this->postId);

        if ($post === null || $post->status !== PostStatus::Deleting) {
            return;
        }

        $account = $post->threadsAccount;

        if ($account === null || $post->threads_media_id === null) {
            return;
        }

        try {
            $threads->deleteMedia($account, $post->threads_media_id);

            // 成功：刪除本地記錄（cascade 刪除關聯的 Reply）
            $post->delete();

            Log::info('Threads post deleted successfully', [
                'post_id' => $this->postId,
                'threads_media_id' => $post->threads_media_id,
            ]);
        } catch (ThreadsApiException $e) {
            if ($e->isTokenInvalid()) {
                $account->update(['status' => ThreadsAccountStatus::NeedsReauth]);
                $post->update([
                    'status' => PostStatus::DeleteFailed,
                    'error_message' => 'token 失效，請重新授權後再次嘗試刪除',
                ]);
            } else {
                $post->update([
                    'status' => PostStatus::DeleteFailed,
                    'error_message' => $e->getMessage(),
                ]);
            }

            Log::warning('Threads post deletion failed', [
                'post_id' => $this->postId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

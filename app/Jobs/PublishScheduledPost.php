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
use Illuminate\Support\Facades\Storage;

class PublishScheduledPost implements ShouldQueue
{
    use Queueable;

    /**
     * Delay between creating the media container and publishing it.
     */
    public const PUBLISH_DELAY_SECONDS = 30;

    /**
     * Maximum number of publish attempts before marking a post as failed.
     */
    public const MAX_PUBLISH_ATTEMPTS = 3;

    /**
     * Base backoff (in seconds) multiplied by the attempt number.
     */
    public const RETRY_BACKOFF_SECONDS = 60;

    public function __construct(
        private readonly int $postId,
        private readonly ?string $creationId = null,
    ) {}

    /**
     * Execute the two-stage publish flow.
     */
    public function handle(ThreadsClient $threads): void
    {
        $post = Post::query()->find($this->postId);

        $expectedStatus = $this->creationId === null
            ? PostStatus::Scheduled
            : PostStatus::Publishing;

        if ($post === null || $post->status !== $expectedStatus) {
            return;
        }

        $account = $post->threadsAccount;

        if ($account === null) {
            return;
        }

        // 停用的使用者：排程貼文不發佈
        if ($post->user !== null && ! $post->user->is_active) {
            return;
        }

        try {
            if ($this->creationId === null) {
                if ($post->image_path !== null) {
                    $imageUrl = Storage::disk('public')->url($post->image_path);
                    $creationId = $threads->createImageContainer($account, $imageUrl, $post->text);
                } else {
                    $creationId = $threads->createTextContainer($account, $post->text);
                }
                $post->update(['status' => PostStatus::Publishing]);

                static::dispatch($this->postId, $creationId)
                    ->delay(now()->addSeconds(self::PUBLISH_DELAY_SECONDS));

                return;
            }

            $mediaId = $threads->publishContainer($account, $this->creationId);

            $post->update([
                'status' => PostStatus::Published,
                'threads_media_id' => $mediaId,
                'published_at' => now(),
                'error_message' => null,
            ]);
        } catch (ThreadsApiException $e) {
            if ($e->isTokenInvalid()) {
                $account->update(['status' => ThreadsAccountStatus::NeedsReauth]);
                $post->update([
                    'status' => PostStatus::Failed,
                    'error_message' => 'token 失效',
                ]);
            } elseif ($e->isRateLimited()) {
                $post->update([
                    'status' => PostStatus::Failed,
                    'error_message' => '已達每日發文上限',
                ]);
            } elseif ($e->isRetryable() && $post->publish_attempts < self::MAX_PUBLISH_ATTEMPTS) {
                $attempt = $post->publish_attempts + 1;
                $post->update(['publish_attempts' => $attempt]);

                static::dispatch($this->postId, $this->creationId)
                    ->delay(now()->addSeconds($attempt * self::RETRY_BACKOFF_SECONDS));
            } else {
                $post->update([
                    'status' => PostStatus::Failed,
                    'error_message' => $e->getMessage(),
                ]);
            }
        } catch (\Throwable $e) {
            $post->update([
                'status' => PostStatus::Failed,
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Threads post publish failed', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

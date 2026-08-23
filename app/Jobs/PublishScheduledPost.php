<?php

namespace App\Jobs;

use App\Enums\PostStatus;
use App\Enums\ThreadsAccountStatus;
use App\Exceptions\ThreadsApiException;
use App\Models\ActivityLog;
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
        private readonly ?array $childIds = null,
    ) {}

    /**
     * Execute the three-stage publish flow.
     */
    public function handle(ThreadsClient $threads): void
    {
        $post = Post::query()->with('images')->find($this->postId);

        // 判斷當前階段
        $isStage1 = $this->creationId === null && $this->childIds === null;
        $isStage2 = $this->creationId === null && $this->childIds !== null;

        $expectedStatus = $isStage1 ? PostStatus::Scheduled : PostStatus::Publishing;

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
            // --- Stage 1: 建立 container(s) ---
            if ($isStage1) {
                // 檢查每日發文上限
                $user = $post->user;
                if ($user !== null && $user->max_daily_posts > 0) {
                    $todayCount = ActivityLog::countTodayForUser($user->id, 'post');
                    if ($todayCount >= $user->max_daily_posts) {
                        $post->update([
                            'status' => PostStatus::Failed,
                            'error_message' => '已達每日發文上限',
                        ]);

                        return;
                    }
                }

                $imageCount = $post->images->count();

                if ($imageCount === 0) {
                    // 純文字
                    $creationId = $threads->createTextContainer($account, $post->text);
                } elseif ($imageCount === 1) {
                    // 單圖
                    $imageUrl = $this->resolveImageUrl($post->images->first()->image_path);
                    $creationId = $threads->createImageContainer($account, $imageUrl, $post->text);
                } else {
                    // 多圖 Carousel: 為每張圖建立 is_carousel_item container
                    $childIds = [];
                    foreach ($post->images as $image) {
                        $imageUrl = $this->resolveImageUrl($image->image_path);
                        $childIds[] = $threads->createCarouselItemContainer($account, $imageUrl);
                    }
                    $post->update(['status' => PostStatus::Publishing]);

                    static::dispatch($this->postId, null, $childIds)
                        ->delay(now()->addSeconds(self::PUBLISH_DELAY_SECONDS));

                    return;
                }

                $post->update(['status' => PostStatus::Publishing]);

                static::dispatch($this->postId, $creationId)
                    ->delay(now()->addSeconds(self::PUBLISH_DELAY_SECONDS));

                return;
            }

            // --- Stage 2: 建立 Carousel container ---
            if ($isStage2) {
                $creationId = $threads->createCarouselContainer($account, $this->childIds, $post->text);

                static::dispatch($this->postId, $creationId)
                    ->delay(now()->addSeconds(self::PUBLISH_DELAY_SECONDS));

                return;
            }

            // --- Stage 3: 發佈 ---
            $mediaId = $threads->publishContainer($account, $this->creationId);

            $post->update([
                'status' => PostStatus::Published,
                'threads_media_id' => $mediaId,
                'published_at' => now(),
                'error_message' => null,
            ]);

            // 寫入 activity_log
            ActivityLog::create([
                'user_id' => $post->user_id,
                'threads_account_id' => $account->id,
                'type' => 'post',
                'reference_id' => $post->id,
                'threads_media_id' => $mediaId,
                'text' => $post->text,
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

                static::dispatch($this->postId, $this->creationId, $this->childIds)
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

    /**
     * 將 image_path 轉換為完整公開 URL。
     */
    private function resolveImageUrl(string $imagePath): string
    {
        if (str_starts_with($imagePath, 'http')) {
            return $imagePath;
        }

        return Storage::disk('public')->url($imagePath);
    }
}

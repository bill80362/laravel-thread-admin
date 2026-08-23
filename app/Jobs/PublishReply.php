<?php

namespace App\Jobs;

use App\Enums\ReplyStatus;
use App\Enums\ThreadsAccountStatus;
use App\Exceptions\ThreadsApiException;
use App\Models\ActivityLog;
use App\Models\Reply;
use App\Services\ReplyService;
use App\Services\ThreadsClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PublishReply implements ShouldQueue
{
    use Queueable;

    /**
     * Maximum number of publish attempts before marking a reply as failed.
     */
    public const MAX_PUBLISH_ATTEMPTS = 3;

    /**
     * Base backoff (in seconds) multiplied by the attempt number.
     */
    public const RETRY_BACKOFF_SECONDS = 60;

    public function __construct(
        public readonly int $replyId,
        public readonly ?string $creationId = null,
        public readonly ?string $replyText = null,
    ) {}

    /**
     * Execute the two-stage publish flow for a reply.
     */
    public function handle(ThreadsClient $threads, ReplyService $replies): void
    {
        $reply = Reply::query()->find($this->replyId);

        $expectedStatus = $this->creationId === null
            ? ReplyStatus::New
            : ReplyStatus::Publishing;

        if ($reply === null || $reply->status !== $expectedStatus) {
            return;
        }

        $account = $reply->threadsAccount;

        if ($account === null) {
            return;
        }

        try {
            if ($this->creationId === null) {
                // 檢查每日回覆上限
                $user = $reply->user;
                if ($user !== null && $user->max_daily_replies > 0) {
                    $todayCount = ActivityLog::countTodayForUser($user->id, 'reply');
                    if ($todayCount >= $user->max_daily_replies) {
                        $reply->update([
                            'status' => ReplyStatus::Failed,
                            'error_message' => '已達每日回覆上限',
                        ]);

                        return;
                    }
                }

                $text = $this->replyText ?? $reply->text;
                $replyToId = $replies->resolveReplyToId($reply);

                $creationId = $threads->createTextContainer($account, $text, $replyToId);
                $reply->update(['status' => ReplyStatus::Publishing]);

                static::dispatch($this->replyId, $creationId)
                    ->delay(now()->addSeconds(PublishScheduledPost::PUBLISH_DELAY_SECONDS));

                return;
            }

            $threads->publishContainer($account, $this->creationId);

            $reply->update([
                'status' => ReplyStatus::Replied,
                'replied_at' => now(),
                'error_message' => null,
            ]);

            // 寫入 activity_log
            ActivityLog::create([
                'user_id' => $reply->user_id,
                'threads_account_id' => $account->id,
                'type' => 'reply',
                'reference_id' => $reply->id,
                'threads_media_id' => null,
                'text' => $reply->text,
            ]);
        } catch (ThreadsApiException $e) {
            if ($e->isTokenInvalid()) {
                $account->update(['status' => ThreadsAccountStatus::NeedsReauth]);
                $reply->update([
                    'status' => ReplyStatus::Failed,
                    'error_message' => 'token 失效',
                ]);
            } elseif ($e->isRateLimited()) {
                $reply->update([
                    'status' => ReplyStatus::Failed,
                    'error_message' => '已達每日發文上限',
                ]);
            } elseif ($e->isRetryable() && $reply->publish_attempts < self::MAX_PUBLISH_ATTEMPTS) {
                $attempt = $reply->publish_attempts + 1;
                $reply->update(['publish_attempts' => $attempt]);

                static::dispatch($this->replyId, $this->creationId, $this->replyText)
                    ->delay(now()->addSeconds($attempt * self::RETRY_BACKOFF_SECONDS));
            } else {
                $reply->update([
                    'status' => ReplyStatus::Failed,
                    'error_message' => $e->getMessage(),
                ]);
            }
        } catch (\Throwable $e) {
            $reply->update([
                'status' => ReplyStatus::Failed,
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Threads reply publish failed', [
                'reply_id' => $reply->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

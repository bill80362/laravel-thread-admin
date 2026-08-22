<?php

namespace App\Services;

use App\Enums\ReplySource;
use App\Enums\ReplyStatus;
use App\Models\Post;
use App\Models\Reply;
use Illuminate\Support\Facades\Log;

class ThreadsWebhookService
{
    /**
     * 處理 Webhook 事件 payload，依 field 分派。
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleEvent(array $payload): void
    {
        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $this->dispatch($change['field'] ?? '', $change['value'] ?? []);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function dispatch(string $field, array $value): void
    {
        match ($field) {
            'replies' => $this->handleReplyCreated($value),
            default => Log::debug('Threads webhook: unsupported field', ['field' => $field]),
        };
    }

    /**
     * 處理回覆事件，以 threads_reply_id 為唯一鍵建立回覆。
     *
     * @param  array<string, mixed>  $value
     */
    private function handleReplyCreated(array $value): void
    {
        $mediaId = $value['media_id'] ?? null;
        $replyId = $value['reply_id'] ?? null;

        if ($mediaId === null || $replyId === null) {
            Log::warning('Threads webhook: reply event missing media_id or reply_id', ['value' => $value]);

            return;
        }

        $post = Post::query()
            ->where('threads_media_id', $mediaId)
            ->first();

        if ($post === null) {
            Log::warning('Threads webhook: no matching post for media_id', ['media_id' => $mediaId]);

            return;
        }

        Reply::query()->firstOrCreate(
            ['threads_reply_id' => $replyId],
            [
                'user_id' => $post->user_id,
                'threads_account_id' => $post->threads_account_id,
                'post_id' => $post->id,
                'author_username' => $value['username'] ?? '',
                'text' => $value['text'] ?? '',
                'source' => ReplySource::Webhook,
                'status' => ReplyStatus::New,
            ],
        );
    }
}

<?php

namespace App\Services;

use App\Enums\ReplySource;
use App\Enums\ReplyStatus;
use App\Jobs\PublishReply;
use App\Models\Post;
use App\Models\Reply;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class ReplyService
{
    /**
     * 建立一筆貼文回覆並排程發佈到 Threads。
     */
    public function createPostReply(int $threadsAccountId, int $postId, string $text): Reply
    {
        $post = Post::query()->find($postId);

        if ($post === null || $post->threads_media_id === null) {
            throw new InvalidArgumentException('目標貼文不存在或尚未發佈，無法回覆');
        }

        $reply = new Reply;
        $reply->threads_account_id = $threadsAccountId;
        $reply->post_id = $postId;
        $reply->threads_reply_id = null;
        $reply->author_username = '';
        $reply->text = $text;
        $reply->source = ReplySource::Manual;
        $reply->status = ReplyStatus::New;
        $reply->save();

        PublishReply::dispatch($reply->id);

        return $reply;
    }

    /**
     * 回應一則留言並排程發佈到 Threads。
     */
    public function publish(Reply $reply, string $text): void
    {
        if ($reply->threads_reply_id === null) {
            throw new InvalidArgumentException('該留言缺少 Threads ID，無法回應');
        }

        PublishReply::dispatch($reply->id, null, $text);
    }

    /**
     * 推導回覆的發佈目標 ID（回覆留言或回覆貼文）。
     */
    public function resolveReplyToId(Reply $reply): string
    {
        if ($reply->threads_reply_id !== null) {
            return $reply->threads_reply_id;
        }

        $post = $reply->post;

        if ($post === null || $post->threads_media_id === null) {
            throw new InvalidArgumentException('無法決定回覆目標');
        }

        return $post->threads_media_id;
    }

    /**
     * 查詢回覆清單，支援依帳號、貼文與狀態篩選。
     *
     * @param  array{threads_account_id?: int, post_id?: int, status?: string}  $filters
     * @return Collection<int, Reply>
     */
    public function list(array $filters = []): Collection
    {
        $query = Reply::query()->with(['threadsAccount', 'post']);

        if (! empty($filters['threads_account_id'])) {
            $query->where('threads_account_id', $filters['threads_account_id']);
        }

        if (! empty($filters['post_id'])) {
            $query->where('post_id', $filters['post_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('created_at')->get();
    }
}

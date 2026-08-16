<?php

namespace App\Services;

use App\Enums\ReplySource;
use App\Enums\ReplyStatus;
use App\Models\Reply;
use Illuminate\Database\Eloquent\Collection;

class ReplyService
{
    /**
     * 建立一筆手動回覆記錄。
     *
     * @param  array{threads_account_id: int, post_id?: int|null, author_username: string, text: string}  $data
     */
    public function create(array $data): Reply
    {
        $reply = new Reply;
        $reply->threads_account_id = $data['threads_account_id'];
        $reply->post_id = $data['post_id'] ?? null;
        $reply->author_username = $data['author_username'];
        $reply->text = $data['text'];
        $reply->source = ReplySource::Manual;
        $reply->status = ReplyStatus::New;
        $reply->save();

        return $reply;
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

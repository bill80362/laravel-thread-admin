<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\ThreadsAccount;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class PostService
{
    /**
     * 建立一筆排程貼文。
     *
     * @param  array{threads_account_id: int, text: string, scheduled_at: string}  $data
     */
    public function create(array $data): Post
    {
        $userId = auth()->id();

        $account = ThreadsAccount::query()
            ->where('user_id', $userId)
            ->find($data['threads_account_id']);

        if ($account === null) {
            throw new InvalidArgumentException('帳號不存在或無權存取');
        }

        $post = new Post;
        $post->user_id = $userId;
        $post->threads_account_id = $data['threads_account_id'];
        $post->text = $data['text'];
        $post->scheduled_at = $data['scheduled_at'];
        $post->status = PostStatus::Scheduled;
        $post->save();

        return $post;
    }

    /**
     * 查詢貼文清單，支援依帳號與狀態篩選。
     *
     * @param  array{threads_account_id?: int, status?: string}  $filters
     * @return Collection<int, Post>
     */
    public function list(array $filters = [], ?int $userId = null): Collection
    {
        $userId ??= auth()->id();

        $query = Post::query()->with('threadsAccount')->where('user_id', $userId);

        if (! empty($filters['threads_account_id'])) {
            $query->where('threads_account_id', $filters['threads_account_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * 查詢單一貼文。
     */
    public function find(int $id, ?int $userId = null): ?Post
    {
        $userId ??= auth()->id();

        return Post::query()->with('threadsAccount')->where('user_id', $userId)->find($id);
    }
}

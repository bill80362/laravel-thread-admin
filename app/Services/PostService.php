<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;

class PostService
{
    /**
     * 建立一筆排程貼文。
     *
     * @param  array{threads_account_id: int, text: string, scheduled_at: string}  $data
     */
    public function create(array $data): Post
    {
        $post = new Post;
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
    public function list(array $filters = []): Collection
    {
        $query = Post::query()->with('threadsAccount');

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
    public function find(int $id): ?Post
    {
        return Post::query()->with('threadsAccount')->find($id);
    }
}

<?php

namespace App\Services;

use App\Enums\PostStatus;
use App\Jobs\DeletePost;
use App\Models\Post;
use App\Models\ThreadsAccount;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class PostService
{
    /**
     * 建立一筆排程貼文。
     *
     * @param  array{threads_account_id: int, text?: string, image_path?: string, image_url?: string, scheduled_at: string}  $data
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

        // 驗證至少要有 text 或 image
        if (empty($data['text']) && empty($data['image_path']) && empty($data['image_url'])) {
            throw new InvalidArgumentException('貼文內容或圖片至少需填寫一項');
        }

        $post = new Post;
        $post->user_id = $userId;
        $post->threads_account_id = $data['threads_account_id'];
        $post->text = $data['text'] ?? null;
        $post->scheduled_at = $data['scheduled_at'];
        $post->status = PostStatus::Scheduled;

        // 處理圖片（來自 Filament 上傳或 MCP image_url）
        if (! empty($data['image_path'])) {
            $post->image_path = $data['image_path'];
        } elseif (! empty($data['image_url'])) {
            $post->image_path = $data['image_url'];
        }

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

    /**
     * 觸發刪除貼文流程。
     * - Published / DeleteFailed：設為 Deleting → dispatch DeletePost job
     * - 其他狀態：直接刪除本地記錄
     */
    public function delete(int $id, ?int $userId = null): void
    {
        $userId ??= auth()->id();

        $post = Post::query()->where('user_id', $userId)->find($id);

        if ($post === null) {
            throw new InvalidArgumentException('貼文不存在或無權存取');
        }

        if (in_array($post->status, [PostStatus::Published, PostStatus::DeleteFailed], true)) {
            $post->update(['status' => PostStatus::Deleting]);
            DeletePost::dispatch($post->id);
        } else {
            $post->delete();
        }
    }
}

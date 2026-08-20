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
     * @param  array{threads_account_id: int, text?: string, image_paths?: string[], image_urls?: string[], scheduled_at: string}  $data
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

        // 收集圖片路徑：統一轉為陣列
        $imagePaths = [];

        if (! empty($data['image_paths'])) {
            $imagePaths = $data['image_paths'];
        } elseif (! empty($data['image_urls'])) {
            $imagePaths = $data['image_urls'];
        }

        // 驗證圖片數量上限
        if (count($imagePaths) > 10) {
            throw new InvalidArgumentException('圖片數量上限為 10 張');
        }

        // 驗證至少要有 text 或 image
        if (empty($data['text']) && empty($imagePaths)) {
            throw new InvalidArgumentException('貼文內容或圖片至少需填寫一項');
        }

        $post = new Post;
        $post->user_id = $userId;
        $post->threads_account_id = $data['threads_account_id'];
        $post->text = $data['text'] ?? null;
        $post->scheduled_at = $data['scheduled_at'];
        $post->status = PostStatus::Scheduled;
        $post->save();

        // 儲存圖片記錄
        foreach ($imagePaths as $index => $path) {
            $post->images()->create([
                'image_path' => $path,
                'sort_order' => $index,
            ]);
        }

        return $post->load('images');
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

        $query = Post::query()->with(['threadsAccount', 'images'])->where('user_id', $userId);

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

        return Post::query()->with(['threadsAccount', 'images'])->where('user_id', $userId)->find($id);
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

<?php

namespace App\Livewire;

use App\Models\Post;
use App\Services\ReplyService;
use Livewire\Component;

class PostReplyDrawer extends Component
{
    public ?int $postId = null;

    public string $replyText = '';

    /**
     * 元件掛載時：載入該貼文回覆，並將該貼文所有回覆標記為已讀。
     */
    public function mount(int $postId): void
    {
        $this->postId = $postId;

        app(ReplyService::class)->markAsRead($postId);
    }

    /**
     * 送出回覆貼文，與 MCP 共用 ReplyService。
     */
    public function sendReply(): void
    {
        $this->validate([
            'replyText' => ['required', 'string', 'max:500'],
        ]);

        $post = Post::query()
            ->where('user_id', auth()->id())
            ->findOrFail($this->postId);

        app(ReplyService::class)->createPostReply(
            $post->threads_account_id,
            $post->id,
            $this->replyText,
        );

        $this->replyText = '';
    }

    /**
     * 渲染抽屜視圖，傳入目標貼文與回覆串（舊 → 新）。
     */
    public function render()
    {
        $post = $this->postId === null ? null : Post::query()
            ->with('threadsAccount')
            ->where('user_id', auth()->id())
            ->find($this->postId);

        $replies = $this->postId === null ? collect() : app(ReplyService::class)->list(['post_id' => $this->postId])
            ->sortBy('created_at')
            ->values();

        return view('livewire.post-reply-drawer', [
            'post' => $post,
            'replies' => $replies,
        ]);
    }
}

<?php

namespace App\Mcp\Tools;

use App\Services\PostService;
use App\Services\ReplyService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('查詢貼文清單，支援依帳號與狀態篩選。每篇貼文會附帶回覆統計（reply_unread_count、reply_total_count）。')]
class ListPostsTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, PostService $posts): Response|ResponseFactory
    {
        $data = $request->validate([
            'threads_account_id' => ['nullable', 'integer', 'exists:threads_accounts,id'],
            'status' => ['nullable', 'string', 'in:draft,scheduled,publishing,published,failed'],
        ]);

        $replies = app(ReplyService::class);

        $result = $posts->list($data)->map(fn ($post): array => [
            'id' => $post->id,
            'threads_account_id' => $post->threads_account_id,
            'threads_media_id' => $post->threads_media_id,
            'text' => $post->text,
            'scheduled_at' => $post->scheduled_at,
            'published_at' => $post->published_at,
            'status' => $post->status->value,
            'error_message' => $post->error_message,
            'reply_unread_count' => $replies->unreadCount($post->id),
            'reply_total_count' => $replies->totalCountForPost($post->id),
        ]);

        return Response::structured(['posts' => $result->all()]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'threads_account_id' => $schema->integer()
                ->description('依帳號 ID 篩選'),
            'status' => $schema->string()
                ->description('依狀態篩選（draft / scheduled / publishing / published / failed）'),
        ];
    }
}

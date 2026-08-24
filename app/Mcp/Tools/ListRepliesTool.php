<?php

namespace App\Mcp\Tools;

use App\Services\ReplyService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('查詢回覆清單，支援依帳號、貼文與狀態篩選，可選擇查詢後標記已讀。')]
class ListRepliesTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, ReplyService $replies): Response|ResponseFactory
    {
        $data = $request->validate([
            'threads_account_id' => ['nullable', 'integer', 'exists:threads_accounts,id'],
            'post_id' => ['nullable', 'integer', 'exists:posts,id'],
            'status' => ['nullable', 'string', 'in:new,replied,ignored'],
            'mark_as_read' => ['nullable', 'boolean'],
        ]);

        $result = $replies->list($data)->map(fn ($reply): array => [
            'id' => $reply->id,
            'threads_account_id' => $reply->threads_account_id,
            'post_id' => $reply->post_id,
            'author_username' => $reply->author_username,
            'text' => $reply->text,
            'status' => $reply->status->value,
            'error_message' => $reply->error_message,
            'replied_at' => $reply->replied_at,
            'read_at' => $reply->read_at,
        ]);

        if ($data['mark_as_read'] ?? false) {
            $replies->markAllAsRead();
        }

        return Response::structured(['replies' => $result->all()]);
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
            'post_id' => $schema->integer()
                ->description('依貼文 ID 篩選'),
            'status' => $schema->string()
                ->description('依狀態篩選（new / replied / ignored）'),
            'mark_as_read' => $schema->boolean()
                ->description('設為 true 時，查詢後將所有未讀回覆標記為已讀'),
        ];
    }
}

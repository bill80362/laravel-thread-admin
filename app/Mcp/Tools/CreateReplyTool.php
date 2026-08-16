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

#[Description('建立一筆貼文回覆並發佈至 Threads，需指定帳號、目標貼文與回覆內容。')]
class CreateReplyTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, ReplyService $replies): Response|ResponseFactory
    {
        $data = $request->validate([
            'threads_account_id' => ['required', 'integer', 'exists:threads_accounts,id'],
            'post_id' => ['required', 'integer', 'exists:posts,id'],
            'text' => ['required', 'string', 'max:500'],
        ]);

        $reply = $replies->createPostReply(
            (int) $data['threads_account_id'],
            (int) $data['post_id'],
            $data['text'],
        );

        return Response::structured([
            'reply' => [
                'id' => $reply->id,
                'threads_account_id' => $reply->threads_account_id,
                'post_id' => $reply->post_id,
                'text' => $reply->text,
                'status' => $reply->status->value,
            ],
        ]);
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
                ->description('來源 Threads 帳號 ID')
                ->required(),
            'post_id' => $schema->integer()
                ->description('目標貼文 ID')
                ->required(),
            'text' => $schema->string()
                ->description('回覆內容（最多 500 字元）')
                ->required(),
        ];
    }
}

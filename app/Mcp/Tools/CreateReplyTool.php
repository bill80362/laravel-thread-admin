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

#[Description('建立一筆手動回覆記錄。來源與狀態會自動設為 manual 與 new。')]
class CreateReplyTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, ReplyService $replies): Response|ResponseFactory
    {
        $data = $request->validate([
            'threads_account_id' => ['required', 'integer', 'exists:threads_accounts,id'],
            'post_id' => ['nullable', 'integer', 'exists:posts,id'],
            'author_username' => ['required', 'string', 'max:255'],
            'text' => ['required', 'string', 'max:500'],
        ]);

        $reply = $replies->create($data);

        return Response::structured([
            'reply' => [
                'id' => $reply->id,
                'threads_account_id' => $reply->threads_account_id,
                'post_id' => $reply->post_id,
                'author_username' => $reply->author_username,
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
                ->description('所屬貼文 ID（可選）'),
            'author_username' => $schema->string()
                ->description('留言者使用者名稱（不含 @）')
                ->required(),
            'text' => $schema->string()
                ->description('留言內容（最多 500 字元）')
                ->required(),
        ];
    }
}

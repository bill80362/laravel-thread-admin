<?php

namespace App\Mcp\Tools;

use App\Services\PostService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('建立一筆排程貼文，需指定帳號、內容與排程時間。')]
class CreatePostTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, PostService $posts): Response|ResponseFactory
    {
        $data = $request->validate([
            'threads_account_id' => ['required', 'integer', 'exists:threads_accounts,id'],
            'text' => ['required', 'string', 'max:500'],
            'scheduled_at' => ['required', 'date'],
        ]);

        $post = $posts->create($data);

        return Response::structured([
            'post' => [
                'id' => $post->id,
                'threads_account_id' => $post->threads_account_id,
                'text' => $post->text,
                'scheduled_at' => $post->scheduled_at,
                'status' => $post->status->value,
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
                ->description('目標 Threads 帳號 ID')
                ->required(),
            'text' => $schema->string()
                ->description('貼文內容（最多 500 字元）')
                ->required(),
            'scheduled_at' => $schema->string()
                ->description('排程時間（ISO 8601 或 Y-m-d H:i:s）')
                ->required(),
        ];
    }
}

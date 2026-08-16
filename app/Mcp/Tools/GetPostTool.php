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

#[Description('依貼文 ID 查詢單一貼文詳細資訊。')]
class GetPostTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, PostService $posts): Response|ResponseFactory
    {
        $data = $request->validate([
            'post_id' => ['required', 'integer'],
        ]);

        $post = $posts->find($data['post_id']);

        if ($post === null) {
            return Response::error("找不到貼文 ID [{$data['post_id']}]");
        }

        return Response::structured([
            'post' => [
                'id' => $post->id,
                'threads_account_id' => $post->threads_account_id,
                'threads_media_id' => $post->threads_media_id,
                'text' => $post->text,
                'scheduled_at' => $post->scheduled_at,
                'published_at' => $post->published_at,
                'status' => $post->status->value,
                'error_message' => $post->error_message,
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
            'post_id' => $schema->integer()
                ->description('貼文 ID')
                ->required(),
        ];
    }
}

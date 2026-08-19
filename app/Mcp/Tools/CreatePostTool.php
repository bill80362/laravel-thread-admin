<?php

namespace App\Mcp\Tools;

use App\Services\PostService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('建立一筆排程貼文，需指定帳號、內容（或圖片 URL）與排程時間。')]
class CreatePostTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, PostService $posts): Response|ResponseFactory
    {
        $data = $request->validate([
            'threads_account_id' => ['required', 'integer', Rule::exists('threads_accounts', 'id')->where('user_id', auth()->id())],
            'text' => ['nullable', 'string', 'max:500'],
            'image_url' => ['nullable', 'string', 'url'],
            'scheduled_at' => ['required', 'date'],
        ]);

        // 驗證至少要有 text 或 image_url
        if (empty($data['text']) && empty($data['image_url'])) {
            return Response::error('貼文內容或圖片 URL 至少需填寫一項');
        }

        $post = $posts->create($data);

        return Response::structured([
            'post' => [
                'id' => $post->id,
                'threads_account_id' => $post->threads_account_id,
                'text' => $post->text,
                'image_path' => $post->image_path,
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
                ->description('貼文內容（最多 500 字元，與圖片至少需填寫一項）'),
            'image_url' => $schema->string()
                ->description('圖片公開 URL（選填，若有則發佈圖文貼文。客戶端需自行上傳圖片到公開 URL）'),
            'scheduled_at' => $schema->string()
                ->description('排程時間（ISO 8601 或 Y-m-d H:i:s）')
                ->required(),
        ];
    }
}

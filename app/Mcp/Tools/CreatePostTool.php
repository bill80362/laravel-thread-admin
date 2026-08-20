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

#[Description('建立一筆排程貼文，需指定帳號、內容（或圖片 URL 陣列）與排程時間。')]
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
            'image_urls' => ['nullable', 'array', 'max:10'],
            'image_urls.*' => ['string', 'url'],
            'scheduled_at' => ['required', 'date'],
        ]);

        $imageUrls = $data['image_urls'] ?? [];

        // 驗證至少要有 text 或 image_urls
        if (empty($data['text']) && empty($imageUrls)) {
            return Response::error('貼文內容或圖片 URL 至少需填寫一項');
        }

        if (count($imageUrls) > 10) {
            return Response::error('圖片數量上限為 10 張');
        }

        $post = $posts->create([
            'threads_account_id' => $data['threads_account_id'],
            'text' => $data['text'] ?? null,
            'image_urls' => $imageUrls,
            'scheduled_at' => $data['scheduled_at'],
        ]);

        return Response::structured([
            'post' => [
                'id' => $post->id,
                'threads_account_id' => $post->threads_account_id,
                'text' => $post->text,
                'images' => $post->images->map(fn ($img) => [
                    'image_path' => $img->image_path,
                    'sort_order' => $img->sort_order,
                ])->toArray(),
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
            'image_urls' => $schema->array()
                ->items($schema->string()->format('uri'))
                ->description('圖片公開 URL 陣列（選填，最多 10 個。客戶端需自行上傳圖片到公開 URL）'),
            'scheduled_at' => $schema->string()
                ->description('排程時間（ISO 8601 或 Y-m-d H:i:s）')
                ->required(),
        ];
    }
}

<?php

namespace App\Mcp\Tools;

use App\Enums\ReplyStatus;
use App\Models\Reply;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('變更回覆留言的處理狀態。status 支援 new（重設為待處理）、read（標記已讀）、ignored（標記已忽略）、replied（標記已回覆）。')]
class UpdateReplyStatusTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'reply_id' => ['required', 'integer', Rule::exists('replies', 'id')->where('user_id', auth()->id())],
            'status' => ['required', 'string', 'in:new,replied,read,ignored'],
        ]);

        $reply = Reply::query()->findOrFail((int) $data['reply_id']);

        $updates = [];

        switch ($data['status']) {
            case 'read':
                $updates['read_at'] = now();
                // 標記「已讀」應保留原始狀態（避免誤改 new → new）
                break;

            case 'ignored':
                $updates['status'] = ReplyStatus::Ignored;
                $updates['read_at'] = now();
                break;

            case 'replied':
                $updates['status'] = ReplyStatus::Replied;
                $updates['replied_at'] = now();
                $updates['read_at'] = now();
                break;

            case 'new':
            default:
                $updates['status'] = ReplyStatus::New;
                $updates['read_at'] = null;
                $updates['error_message'] = null;
                break;
        }

        $reply->update($updates);
        $reply->refresh();

        return Response::structured([
            'reply' => [
                'id' => $reply->id,
                'threads_account_id' => $reply->threads_account_id,
                'post_id' => $reply->post_id,
                'text' => $reply->text,
                'status' => $reply->status->value,
                'read_at' => $reply->read_at,
                'replied_at' => $reply->replied_at,
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
            'reply_id' => $schema->integer()
                ->description('目標回覆留言 ID')
                ->required(),
            'status' => $schema->string()
                ->description('目標狀態：new / read / ignored / replied')
                ->required(),
        ];
    }
}

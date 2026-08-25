<?php

namespace App\Mcp\Tools;

use App\Models\Reply;
use App\Services\ReplyService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('對指定的回覆留言發出回應，系統會自動排程發佈至 Threads。若該留言缺少 Threads ID（非從 Threads 收集而來），會回退為回應該留言所屬的貼文。')]
class ReplyToReplyTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, ReplyService $replies): Response|ResponseFactory
    {
        $data = $request->validate([
            'reply_id' => ['required', 'integer', Rule::exists('replies', 'id')->where('user_id', auth()->id())],
            'text' => ['required', 'string', 'max:500'],
        ]);

        $reply = Reply::query()->findOrFail((int) $data['reply_id']);

        if ($reply->threads_reply_id === null) {
            // 該留言缺少 Threads ID（非從 Threads 收集而來），回退為回應該留言所屬的貼文。
            // createPostReply 會建立一筆新的回覆記錄並回傳。
            $newReply = $replies->createPostReply(
                $reply->threads_account_id,
                $reply->post_id,
                $data['text'],
            );
            $reply = $newReply;
        } else {
            // 對 Threads 留言回應（直接使用既有留言記錄排程發佈）。
            $replies->publish($reply, $data['text']);
            $reply->refresh();
        }

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
            'reply_id' => $schema->integer()
                ->description('目標回覆留言 ID')
                ->required(),
            'text' => $schema->string()
                ->description('回應內容（最多 500 字元）')
                ->required(),
        ];
    }
}

<?php

namespace App\Mcp\Tools;

use App\Models\ThreadsAccount;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('列出已綁定的 Threads 帳號，供發文與回覆使用。可依狀態篩選。')]
class ListAccountsTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $status = $request->get('status');

        $accounts = ThreadsAccount::query()
            ->where('user_id', auth()->id())
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderBy('id')
            ->get()
            ->map(fn (ThreadsAccount $account): array => [
                'id' => $account->id,
                'username' => $account->username,
                'name' => $account->name,
                'status' => $account->status->value,
            ]);

        return Response::structured(['accounts' => $accounts->all()]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()
                ->description('依帳號狀態篩選（active / needs_reauth / disabled）。留空回傳全部。'),
        ];
    }
}

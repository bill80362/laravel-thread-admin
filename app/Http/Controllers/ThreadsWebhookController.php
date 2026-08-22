<?php

namespace App\Http\Controllers;

use App\Services\ThreadsWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ThreadsWebhookController extends Controller
{
    public function __construct(private readonly ThreadsWebhookService $service) {}

    /**
     * 處理 Webhook 訂閱驗證（GET）與事件接收（POST）。
     */
    public function handle(Request $request): Response|JsonResponse
    {
        if ($request->isMethod('get')) {
            return $this->verify($request);
        }

        $this->service->handleEvent($request->all());

        return response()->json(['status' => 'ok']);
    }

    private function verify(Request $request): Response
    {
        $query = $request->query();

        // PHP parse_str 會將 query key 中的點號轉為底線（hub.mode → hub_mode）。
        $mode = $query['hub_mode'] ?? null;
        $token = $query['hub_verify_token'] ?? null;
        $challenge = $query['hub_challenge'] ?? null;

        $expected = config('services.threads.webhook_verify_token');

        if ($mode === 'subscribe' && $token === $expected && $challenge !== null) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ImeiOrder;
use App\Models\ImeiProvider;
use App\Services\Imei\ImeiOrderOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImeiWebhookController extends Controller
{
    public function handle(Request $request, ImeiProvider $provider, ImeiOrderOrchestrator $orchestrator): JsonResponse
    {
        $payload = $request->all();
        $providerOrderId = (string) ($payload['provider_order_id'] ?? $payload['order_id'] ?? '');

        if ($providerOrderId === '') {
            return response()->json(['message' => 'Missing provider_order_id'], 422);
        }

        $order = ImeiOrder::query()
            ->where('imei_provider_id', $provider->id)
            ->where('provider_order_id', $providerOrderId)
            ->first();

        if ($order === null) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $orchestrator->applyWebhookUpdate($order, [
            'provider_order_id' => $providerOrderId,
            'status' => $payload['status'] ?? $order->status,
            'result' => $payload['result'] ?? $payload,
            'raw' => $payload,
        ]);

        return response()->json(['message' => 'Webhook processed']);
    }
}

<?php

namespace App\Services\Imei\Providers;

use App\Models\ImeiOrder;
use App\Models\ImeiProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class UnlockBaseProviderClient extends GenericRestProviderClient
{
    public function submitOrder(ImeiProvider $provider, ImeiOrder $order): array
    {
        $response = Http::timeout(20)
            ->acceptJson()
            ->withHeaders(['X-API-KEY' => (string) $provider->api_key])
            ->post(rtrim((string) $provider->api_url, '/').Arr::get($provider->config, 'submit_path', '/api/v1/order'), [
                'imei' => $order->imei,
                'service_id' => Arr::get($provider->config, 'service_map.'.$order->service->slug, $order->service->slug),
                'reference' => $order->order_number,
            ]);

        $payload = $response->json() ?? [];

        return [
            'ok' => (bool) ($payload['success'] ?? false),
            'provider_order_id' => (string) ($payload['data']['id'] ?? ''),
            'status' => (string) ($payload['data']['status'] ?? 'submitted'),
            'cost' => (float) ($payload['data']['price'] ?? 0),
            'message' => (string) ($payload['message'] ?? ''),
            'raw' => $payload,
        ];
    }
}

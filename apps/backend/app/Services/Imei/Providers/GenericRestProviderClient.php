<?php

namespace App\Services\Imei\Providers;

use App\Models\ImeiOrder;
use App\Models\ImeiProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class GenericRestProviderClient implements ImeiProviderClientInterface
{
    public function submitOrder(ImeiProvider $provider, ImeiOrder $order): array
    {
        $submitPath = Arr::get($provider->config, 'submit_path', '/submit');

        $response = Http::timeout(20)
            ->acceptJson()
            ->withHeaders($this->headers($provider))
            ->post($this->url($provider, $submitPath), [
                'service' => $order->service->slug,
                'imei' => $order->imei,
                'client_order_id' => $order->order_number,
            ]);

        return $this->normalizeResponse($response->json() ?? []);
    }

    public function fetchOrderStatus(ImeiProvider $provider, ImeiOrder $order): array
    {
        $statusPath = Arr::get($provider->config, 'status_path', '/status');

        $response = Http::timeout(20)
            ->acceptJson()
            ->withHeaders($this->headers($provider))
            ->get($this->url($provider, $statusPath), [
                'order_id' => $order->provider_order_id,
                'client_order_id' => $order->order_number,
            ]);

        return $this->normalizeStatus($response->json() ?? []);
    }

    public function fetchBalance(ImeiProvider $provider): array
    {
        $balancePath = Arr::get($provider->config, 'balance_path', '/balance');

        $response = Http::timeout(20)
            ->acceptJson()
            ->withHeaders($this->headers($provider))
            ->get($this->url($provider, $balancePath));

        $payload = $response->json() ?? [];

        return [
            'balance' => (float) ($payload['balance'] ?? 0),
            'currency' => (string) ($payload['currency'] ?? 'USD'),
            'raw' => $payload,
        ];
    }

    protected function headers(ImeiProvider $provider): array
    {
        return [
            'X-API-KEY' => (string) ($provider->api_key ?? ''),
            'X-API-SECRET' => (string) ($provider->api_secret ?? ''),
        ];
    }

    protected function url(ImeiProvider $provider, string $path): string
    {
        $base = rtrim((string) $provider->api_url, '/');

        return $base.$path;
    }

    protected function normalizeResponse(array $payload): array
    {
        return [
            'ok' => (bool) ($payload['success'] ?? $payload['ok'] ?? false),
            'provider_order_id' => (string) ($payload['order_id'] ?? $payload['id'] ?? ''),
            'status' => (string) ($payload['status'] ?? 'submitted'),
            'cost' => (float) ($payload['cost'] ?? 0),
            'message' => (string) ($payload['message'] ?? ''),
            'raw' => $payload,
        ];
    }

    protected function normalizeStatus(array $payload): array
    {
        return [
            'status' => (string) ($payload['status'] ?? 'processing'),
            'result' => $payload['result'] ?? $payload,
            'raw' => $payload,
        ];
    }
}

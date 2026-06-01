<?php

namespace App\Services\Imei\Providers;

use App\Models\ImeiOrder;
use App\Models\ImeiProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class GenericXmlProviderClient implements ImeiProviderClientInterface
{
    public function submitOrder(ImeiProvider $provider, ImeiOrder $order): array
    {
        $endpoint = Arr::get($provider->config, 'submit_path', '/submit');
        $xml = $this->toXml('order', [
            'imei' => $order->imei,
            'service' => $order->service->slug,
            'client_order_id' => $order->order_number,
            'username' => $provider->username,
            'password' => $provider->password,
        ]);

        $response = Http::timeout(20)
            ->withHeaders(['Content-Type' => 'application/xml'])
            ->withBody($xml, 'application/xml')
            ->post(rtrim((string) $provider->api_url, '/').$endpoint);

        $payload = $this->fromXml($response->body());

        return [
            'ok' => in_array(strtolower((string) ($payload['success'] ?? 'false')), ['1', 'true', 'yes'], true),
            'provider_order_id' => (string) ($payload['order_id'] ?? ''),
            'status' => (string) ($payload['status'] ?? 'submitted'),
            'cost' => (float) ($payload['cost'] ?? 0),
            'message' => (string) ($payload['message'] ?? ''),
            'raw' => $payload,
        ];
    }

    public function fetchOrderStatus(ImeiProvider $provider, ImeiOrder $order): array
    {
        $endpoint = Arr::get($provider->config, 'status_path', '/status');
        $xml = $this->toXml('status', [
            'order_id' => $order->provider_order_id,
            'client_order_id' => $order->order_number,
            'username' => $provider->username,
            'password' => $provider->password,
        ]);

        $response = Http::timeout(20)
            ->withHeaders(['Content-Type' => 'application/xml'])
            ->withBody($xml, 'application/xml')
            ->post(rtrim((string) $provider->api_url, '/').$endpoint);

        $payload = $this->fromXml($response->body());

        return [
            'status' => (string) ($payload['status'] ?? 'processing'),
            'result' => $payload['result'] ?? $payload,
            'raw' => $payload,
        ];
    }

    public function fetchBalance(ImeiProvider $provider): array
    {
        $endpoint = Arr::get($provider->config, 'balance_path', '/balance');
        $xml = $this->toXml('balance', [
            'username' => $provider->username,
            'password' => $provider->password,
        ]);

        $response = Http::timeout(20)
            ->withHeaders(['Content-Type' => 'application/xml'])
            ->withBody($xml, 'application/xml')
            ->post(rtrim((string) $provider->api_url, '/').$endpoint);

        $payload = $this->fromXml($response->body());

        return [
            'balance' => (float) ($payload['balance'] ?? 0),
            'currency' => (string) ($payload['currency'] ?? 'USD'),
            'raw' => $payload,
        ];
    }

    private function toXml(string $root, array $payload): string
    {
        $xml = new \SimpleXMLElement('<'.$root.'/>');

        foreach ($payload as $key => $value) {
            $xml->addChild((string) $key, htmlspecialchars((string) $value));
        }

        return $xml->asXML() ?: '';
    }

    /**
     * @return array<string, mixed>
     */
    private function fromXml(string $body): array
    {
        if ($body === '') {
            return [];
        }

        $xml = @simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($xml === false) {
            return [];
        }

        return json_decode(json_encode($xml), true) ?? [];
    }
}

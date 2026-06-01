<?php

namespace App\Services\Imei\Providers;

use App\Models\ImeiOrder;
use App\Models\ImeiProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class DhruFusionProviderClient extends GenericXmlProviderClient
{
    public function submitOrder(ImeiProvider $provider, ImeiOrder $order): array
    {
        $xml = new \SimpleXMLElement('<request/>');
        $xml->addChild('username', (string) $provider->username);
        $xml->addChild('api_key', (string) $provider->api_key);
        $xml->addChild('service_id', (string) Arr::get($provider->config, 'service_map.'.$order->service->slug, $order->service->slug));
        $xml->addChild('imei', (string) $order->imei);
        $xml->addChild('reference', (string) $order->order_number);

        $response = Http::timeout(20)
            ->withHeaders(['Content-Type' => 'application/xml'])
            ->withBody($xml->asXML() ?: '', 'application/xml')
            ->post(rtrim((string) $provider->api_url, '/').Arr::get($provider->config, 'submit_path', '/api/orders'));

        $payload = json_decode(json_encode(simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA) ?: []), true) ?? [];

        return [
            'ok' => strtolower((string) ($payload['status'] ?? '')) === 'success',
            'provider_order_id' => (string) ($payload['orderid'] ?? ''),
            'status' => strtolower((string) ($payload['orderstatus'] ?? 'submitted')),
            'cost' => (float) ($payload['price'] ?? 0),
            'message' => (string) ($payload['message'] ?? ''),
            'raw' => $payload,
        ];
    }
}

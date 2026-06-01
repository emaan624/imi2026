<?php

namespace App\Services\Imei;

use App\Models\ImeiOrder;
use App\Models\ImeiOrderLog;
use App\Models\ImeiProvider;
use App\Models\User;
use App\Services\Imei\Checkers\ImeiCheckerService;
use App\Services\Imei\Providers\ImeiProviderClientFactory;
use Illuminate\Support\Facades\Log;

class ImeiOrderOrchestrator
{
    public function __construct(
        private readonly ImeiProviderClientFactory $providerFactory,
        private readonly ImeiCheckerService $checkerService,
    ) {}

    public function submitWithFallback(ImeiOrder $order, ?User $actor = null): ImeiOrder
    {
        $providers = ImeiProvider::query()
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();

        foreach ($providers as $provider) {
            try {
                $client = $this->providerFactory->make($provider);
                $response = $client->submitOrder($provider, $order->fresh('service'));

                if (! ($response['ok'] ?? false)) {
                    $this->log($order, $actor, 'provider_submit_failed', $order->status, $order->status, $response['message'] ?? 'Provider submission failed', [
                        'provider_id' => $provider->id,
                        'response' => $response,
                    ]);
                    continue;
                }

                $order->update([
                    'imei_provider_id' => $provider->id,
                    'provider_order_id' => $response['provider_order_id'] ?: null,
                    'provider_cost' => $response['cost'] ?? 0,
                    'status' => $this->mapStatus((string) ($response['status'] ?? 'submitted')),
                    'submitted_at' => now(),
                    'meta' => array_merge($order->meta ?? [], ['provider_submit_response' => $response['raw'] ?? []]),
                ]);

                $this->log($order->fresh(), $actor, 'provider_submit_success', 'pending_submission', $order->fresh()->status, 'IMEI order submitted to provider', [
                    'provider_id' => $provider->id,
                ]);

                return $order->fresh();
            } catch (\Throwable $throwable) {
                Log::warning('IMEI provider fallback triggered', [
                    'order_id' => $order->id,
                    'provider_id' => $provider->id,
                    'error' => $throwable->getMessage(),
                ]);

                $this->log($order, $actor, 'provider_exception', $order->status, $order->status, $throwable->getMessage(), [
                    'provider_id' => $provider->id,
                ]);
            }
        }

        $order->update(['status' => 'failed']);
        $this->log($order->fresh(), $actor, 'fallback_exhausted', 'pending_submission', 'failed', 'All providers failed.');

        return $order->fresh();
    }

    public function syncOrderStatus(ImeiOrder $order, ?User $actor = null): ImeiOrder
    {
        if ($order->provider === null) {
            return $order;
        }

        $fromStatus = $order->status;
        $client = $this->providerFactory->make($order->provider);
        $statusPayload = $client->fetchOrderStatus($order->provider, $order);
        $toStatus = $this->mapStatus((string) ($statusPayload['status'] ?? $order->status));

        $order->update([
            'status' => $toStatus,
            'completed_at' => in_array($toStatus, ['completed', 'failed', 'refunded'], true) ? now() : $order->completed_at,
            'result' => $statusPayload['result'] ?? $order->result,
            'meta' => array_merge($order->meta ?? [], ['last_status_sync' => $statusPayload['raw'] ?? []]),
        ]);

        if ($toStatus === 'completed' && ! empty($order->service?->checker_type)) {
            $order->update([
                'result' => $this->checkerService->check((string) $order->service->checker_type, $order->imei),
            ]);
        }

        $this->log($order->fresh(), $actor, 'status_sync', $fromStatus, $toStatus, 'Status synchronized from provider.');

        return $order->fresh();
    }

    public function applyWebhookUpdate(ImeiOrder $order, array $payload): ImeiOrder
    {
        $fromStatus = $order->status;
        $toStatus = $this->mapStatus((string) ($payload['status'] ?? $fromStatus));

        $order->update([
            'status' => $toStatus,
            'provider_order_id' => $payload['provider_order_id'] ?? $order->provider_order_id,
            'result' => $payload['result'] ?? $order->result,
            'meta' => array_merge($order->meta ?? [], ['webhook_payload' => $payload]),
            'completed_at' => in_array($toStatus, ['completed', 'failed', 'refunded'], true) ? now() : $order->completed_at,
        ]);

        $this->log($order->fresh(), null, 'webhook_status_update', $fromStatus, $toStatus, 'Order updated from webhook.', [
            'payload' => $payload,
        ]);

        return $order->fresh();
    }

    private function mapStatus(string $providerStatus): string
    {
        return match (strtolower($providerStatus)) {
            'submitted', 'accepted', 'pending', 'pending_submission' => 'submitted',
            'processing', 'in_progress', 'in-review', 'in_review' => 'processing',
            'done', 'success', 'completed', 'delivered' => 'completed',
            'refund', 'refunded' => 'refunded',
            'failed', 'error', 'rejected', 'cancelled', 'canceled' => 'failed',
            default => 'processing',
        };
    }

    private function log(ImeiOrder $order, ?User $actor, string $action, ?string $fromStatus = null, ?string $toStatus = null, ?string $notes = null, ?array $meta = null): void
    {
        ImeiOrderLog::create([
            'imei_order_id' => $order->id,
            'user_id' => $actor?->id,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'notes' => $notes,
            'meta' => $meta,
        ]);
    }
}

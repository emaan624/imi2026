<?php

namespace App\Jobs;

use App\Models\ImeiProvider;
use App\Models\ImeiProviderBalance;
use App\Services\Imei\Providers\ImeiProviderClientFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncImeiProviderBalancesJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public ?int $providerId = null) {}

    public function handle(ImeiProviderClientFactory $factory): void
    {
        $query = ImeiProvider::query()->where('is_active', true);

        if ($this->providerId !== null) {
            $query->whereKey($this->providerId);
        }

        $query->chunkById(100, function ($providers) use ($factory): void {
            foreach ($providers as $provider) {
                $client = $factory->make($provider);
                $balance = $client->fetchBalance($provider);

                ImeiProviderBalance::updateOrCreate(
                    ['imei_provider_id' => $provider->id],
                    [
                        'balance' => $balance['balance'] ?? 0,
                        'currency' => $balance['currency'] ?? 'USD',
                        'raw_payload' => $balance['raw'] ?? null,
                        'last_synced_at' => now(),
                    ]
                );
            }
        });
    }
}

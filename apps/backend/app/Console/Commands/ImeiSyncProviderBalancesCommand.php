<?php

namespace App\Console\Commands;

use App\Jobs\SyncImeiProviderBalancesJob;
use Illuminate\Console\Command;

class ImeiSyncProviderBalancesCommand extends Command
{
    protected $signature = 'imei:sync-balances {providerId?}';

    protected $description = 'Dispatch IMEI provider balance synchronization job';

    public function handle(): int
    {
        $providerId = $this->argument('providerId');

        SyncImeiProviderBalancesJob::dispatch($providerId !== null ? (int) $providerId : null);
        $this->info('Dispatched IMEI provider balance sync job.');

        return self::SUCCESS;
    }
}

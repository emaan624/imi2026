<?php

namespace App\Console\Commands;

use App\Jobs\SyncImeiOrderStatusesJob;
use Illuminate\Console\Command;

class ImeiSyncStatusesCommand extends Command
{
    protected $signature = 'imei:sync-statuses {orderId?}';

    protected $description = 'Dispatch IMEI order status synchronization job';

    public function handle(): int
    {
        $orderId = $this->argument('orderId');

        SyncImeiOrderStatusesJob::dispatch($orderId !== null ? (int) $orderId : null);
        $this->info('Dispatched IMEI order status sync job.');

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Jobs\SubmitImeiOrderJob;
use App\Models\ImeiOrder;
use Illuminate\Console\Command;

class ImeiSubmitPendingOrdersCommand extends Command
{
    protected $signature = 'imei:submit-pending {--limit=200}';

    protected $description = 'Dispatch jobs for pending IMEI orders';

    public function handle(): int
    {
        $limit = max((int) $this->option('limit'), 1);

        $orders = ImeiOrder::query()
            ->where('status', 'pending_submission')
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        foreach ($orders as $orderId) {
            SubmitImeiOrderJob::dispatch((int) $orderId);
        }

        $this->info('Dispatched '.$orders->count().' IMEI order submission jobs.');

        return self::SUCCESS;
    }
}

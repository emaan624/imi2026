<?php

namespace App\Jobs;

use App\Models\ImeiOrder;
use App\Models\ImeiBulkOrder;
use App\Services\Imei\ImeiOrderOrchestrator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncImeiOrderStatusesJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public ?int $orderId = null) {}

    public function handle(ImeiOrderOrchestrator $orchestrator): void
    {
        $query = ImeiOrder::with(['provider', 'service'])
            ->whereIn('status', ['submitted', 'processing']);

        if ($this->orderId !== null) {
            $query->whereKey($this->orderId);
        }

        $query->chunkById(100, function ($orders) use ($orchestrator): void {
            foreach ($orders as $order) {
                $orchestrator->syncOrderStatus($order);
            }
        });

        ImeiBulkOrder::query()->whereIn('status', ['pending', 'processing'])->chunkById(100, function ($bulks): void {
            foreach ($bulks as $bulk) {
                $orders = $bulk->orders();
                $processed = (clone $orders)->whereIn('status', ['completed', 'failed', 'refunded'])->count();
                $successful = (clone $orders)->where('status', 'completed')->count();
                $failed = (clone $orders)->whereIn('status', ['failed', 'refunded'])->count();
                $total = (clone $orders)->count();
                $status = $processed >= $total && $total > 0 ? 'completed' : 'processing';

                $bulk->update([
                    'status' => $status,
                    'total_orders' => $total,
                    'processed_orders' => $processed,
                    'successful_orders' => $successful,
                    'failed_orders' => $failed,
                ]);
            }
        });
    }
}

<?php

namespace App\Jobs;

use App\Models\ImeiOrder;
use App\Services\Imei\ImeiOrderOrchestrator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SubmitImeiOrderJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $orderId) {}

    public function handle(ImeiOrderOrchestrator $orchestrator): void
    {
        $order = ImeiOrder::with(['service', 'provider'])->find($this->orderId);

        if ($order === null || $order->status !== 'pending_submission') {
            return;
        }

        $orchestrator->submitWithFallback($order);
    }
}

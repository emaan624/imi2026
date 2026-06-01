<?php

namespace App\Services\Imei\Providers;

use App\Models\ImeiOrder;
use App\Models\ImeiProvider;

interface ImeiProviderClientInterface
{
    public function submitOrder(ImeiProvider $provider, ImeiOrder $order): array;

    public function fetchOrderStatus(ImeiProvider $provider, ImeiOrder $order): array;

    public function fetchBalance(ImeiProvider $provider): array;
}

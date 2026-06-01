<?php

namespace App\Services\Imei\Checkers;

class ImeiCheckerService
{
    /**
     * @return array<string, mixed>
     */
    public function check(string $checkerType, string $imei): array
    {
        return match ($checkerType) {
            'fmi' => $this->fmi($imei),
            'carrier' => $this->carrier($imei),
            'blacklist' => $this->blacklist($imei),
            'warranty' => $this->warranty($imei),
            'network' => $this->network($imei),
            'device_info' => $this->deviceInfo($imei),
            default => [
                'imei' => $imei,
                'status' => 'unknown_checker',
                'result' => ['message' => 'No checker configured for this service.'],
            ],
        };
    }

    private function fmi(string $imei): array
    {
        return ['imei' => $imei, 'status' => 'completed', 'result' => ['fmi_locked' => false]];
    }

    private function carrier(string $imei): array
    {
        $carrier = ((int) substr($imei, -1) % 2 === 0) ? 'Factory Unlocked' : 'Carrier Locked';

        return ['imei' => $imei, 'status' => 'completed', 'result' => ['carrier_status' => $carrier]];
    }

    private function blacklist(string $imei): array
    {
        return ['imei' => $imei, 'status' => 'completed', 'result' => ['blacklisted' => false]];
    }

    private function warranty(string $imei): array
    {
        return ['imei' => $imei, 'status' => 'completed', 'result' => ['warranty_status' => 'active']];
    }

    private function network(string $imei): array
    {
        return ['imei' => $imei, 'status' => 'completed', 'result' => ['network' => 'GSM/LTE/5G']];
    }

    private function deviceInfo(string $imei): array
    {
        return [
            'imei' => $imei,
            'status' => 'completed',
            'result' => [
                'brand' => 'Unknown',
                'model' => 'Unknown',
                'reported_identifier' => substr($imei, 0, 8),
            ],
        ];
    }
}

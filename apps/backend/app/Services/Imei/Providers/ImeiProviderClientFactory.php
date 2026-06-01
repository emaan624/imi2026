<?php

namespace App\Services\Imei\Providers;

use App\Models\ImeiProvider;

class ImeiProviderClientFactory
{
    public function make(ImeiProvider $provider): ImeiProviderClientInterface
    {
        return match ($provider->type) {
            'unlockbase' => new UnlockBaseProviderClient(),
            'dhru_fusion' => new DhruFusionProviderClient(),
            'generic_xml' => new GenericXmlProviderClient(),
            default => new GenericRestProviderClient(),
        };
    }
}

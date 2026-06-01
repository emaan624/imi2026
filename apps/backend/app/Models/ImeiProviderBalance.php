<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImeiProviderBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'imei_provider_id',
        'balance',
        'currency',
        'last_synced_at',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'last_synced_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ImeiProvider::class, 'imei_provider_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PtaOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pta_service_id',
        'order_number',
        'imei_1',
        'imei_2',
        'sim_type',
        'registration_type',
        'passport_number',
        'cnic_number',
        'status',
        'tax_amount',
        'service_fee',
        'total_amount',
        'paid_amount',
        'wallet_payment_amount',
        'approved_at',
        'external_reference',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'tax_amount' => 'decimal:2',
            'service_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'wallet_payment_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(PtaService::class, 'pta_service_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PtaOrderLog::class);
    }
}

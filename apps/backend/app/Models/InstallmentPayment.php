<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallmentPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'installment_contract_id',
        'installment_schedule_id',
        'wallet_id',
        'amount',
        'principal_amount',
        'late_fee_amount',
        'payment_method',
        'status',
        'reference',
        'paid_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'principal_amount' => 'decimal:2',
            'late_fee_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(InstallmentContract::class, 'installment_contract_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(InstallmentSchedule::class, 'installment_schedule_id');
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}

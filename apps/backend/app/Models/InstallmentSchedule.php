<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstallmentSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'installment_contract_id',
        'installment_no',
        'due_date',
        'grace_until',
        'amount',
        'late_fee_amount',
        'status',
        'paid_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'grace_until' => 'date',
            'amount' => 'decimal:2',
            'late_fee_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(InstallmentContract::class, 'installment_contract_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InstallmentPayment::class);
    }
}

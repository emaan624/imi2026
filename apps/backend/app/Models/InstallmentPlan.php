<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstallmentPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'device_price',
        'down_payment',
        'installment_amount',
        'installment_count',
        'frequency',
        'grace_period_days',
        'late_fee_type',
        'late_fee_value',
        'is_active',
        'risk_level',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'device_price' => 'decimal:2',
            'down_payment' => 'decimal:2',
            'installment_amount' => 'decimal:2',
            'late_fee_value' => 'decimal:2',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(InstallmentContract::class);
    }
}

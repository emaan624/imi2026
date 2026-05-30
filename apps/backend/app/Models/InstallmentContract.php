<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstallmentContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'installment_plan_id',
        'pta_order_id',
        'approved_by',
        'contract_number',
        'status',
        'verification_status',
        'total_amount',
        'down_payment',
        'paid_amount',
        'remaining_balance',
        'auto_deduction',
        'risk_score',
        'start_date',
        'end_date',
        'next_due_date',
        'approved_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'down_payment' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
            'auto_deduction' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
            'next_due_date' => 'date',
            'approved_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(InstallmentPlan::class, 'installment_plan_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PtaOrder::class, 'pta_order_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(InstallmentSchedule::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InstallmentPayment::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(InstallmentReminder::class);
    }
}

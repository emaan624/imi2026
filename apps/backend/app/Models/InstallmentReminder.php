<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallmentReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'installment_contract_id',
        'installment_schedule_id',
        'type',
        'channel',
        'status',
        'scheduled_at',
        'sent_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
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
}

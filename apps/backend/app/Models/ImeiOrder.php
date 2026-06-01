<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImeiOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'imei_service_id',
        'imei_provider_id',
        'imei_bulk_order_id',
        'order_number',
        'provider_order_id',
        'imei',
        'status',
        'price',
        'provider_cost',
        'refunded_amount',
        'result',
        'meta',
        'submitted_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'provider_cost' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
            'result' => 'array',
            'meta' => 'array',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(ImeiService::class, 'imei_service_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ImeiProvider::class, 'imei_provider_id');
    }

    public function bulkOrder(): BelongsTo
    {
        return $this->belongsTo(ImeiBulkOrder::class, 'imei_bulk_order_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ImeiOrderLog::class, 'imei_order_id');
    }
}

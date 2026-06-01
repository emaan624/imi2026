<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImeiBulkOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bulk_number',
        'status',
        'total_orders',
        'processed_orders',
        'successful_orders',
        'failed_orders',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'total_orders' => 'integer',
            'processed_orders' => 'integer',
            'successful_orders' => 'integer',
            'failed_orders' => 'integer',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ImeiOrder::class, 'imei_bulk_order_id');
    }
}

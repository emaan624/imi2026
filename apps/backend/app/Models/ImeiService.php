<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImeiService extends Model
{
    use HasFactory;

    protected $fillable = [
        'imei_category_id',
        'name',
        'slug',
        'checker_type',
        'price',
        'estimated_time_minutes',
        'is_active',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'estimated_time_minutes' => 'integer',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ImeiCategory::class, 'imei_category_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ImeiOrder::class, 'imei_service_id');
    }
}

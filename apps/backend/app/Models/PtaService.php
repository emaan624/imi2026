<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PtaService extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'base_fee',
        'is_active',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'base_fee' => 'decimal:2',
            'is_active' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(PtaOrder::class);
    }
}

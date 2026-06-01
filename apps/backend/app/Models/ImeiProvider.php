<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ImeiProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'priority',
        'api_url',
        'api_key',
        'api_secret',
        'username',
        'password',
        'supports_webhooks',
        'is_active',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'supports_webhooks' => 'boolean',
            'is_active' => 'boolean',
            'config' => 'array',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ImeiOrder::class, 'imei_provider_id');
    }

    public function balance(): HasOne
    {
        return $this->hasOne(ImeiProviderBalance::class, 'imei_provider_id');
    }
}

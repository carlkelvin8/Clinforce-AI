<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BackgroundCheckProvider extends Model
{
    protected $table = 'background_check_providers';

    protected $fillable = [
        'name', 'display_name', 'description', 'config', 'is_active',
        'supported_types', 'pricing', 'webhook_url',
    ];

    protected $casts = [
        'config' => 'encrypted:array',
        'is_active' => 'boolean',
        'supported_types' => 'array',
        'pricing' => 'array',
    ];

    public function backgroundChecks(): HasMany
    {
        return $this->hasMany(BackgroundCheck::class, 'provider', 'name');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function supportsType(string $type): bool
    {
        return in_array($type, $this->supported_types ?? []);
    }

    public function getPriceForType(string $type): ?float
    {
        return $this->pricing[$type] ?? null;
    }
}

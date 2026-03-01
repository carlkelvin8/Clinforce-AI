<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $table = 'notifications';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'role',
        'category',
        'type',
        'title',
        'body',
        'data',
        'url',
        'is_read',
        'batch_key',
        'batch_count',
        'created_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'batch_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function pushNotification(array $attributes): self
    {
        $attributes['created_at'] = $attributes['created_at'] ?? now();
        $attributes['batch_count'] = $attributes['batch_count'] ?? 1;
        $attributes['is_read'] = $attributes['is_read'] ?? false;

        $batchKey = $attributes['batch_key'] ?? null;
        if ($batchKey) {
            $existing = static::query()
                ->where('user_id', $attributes['user_id'] ?? 0)
                ->where('batch_key', $batchKey)
                ->where('created_at', '>=', now()->subMinutes(10))
                ->orderByDesc('id')
                ->first();
            if ($existing) {
                $existing->batch_count = ($existing->batch_count ?? 1) + 1;
                $existing->created_at = now();
                $existing->save();
                return $existing;
            }
        }

        return static::query()->create($attributes);
    }
}

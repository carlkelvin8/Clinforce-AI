<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobRecommendation extends Model
{
    protected $fillable = [
        'user_id',
        'job_id',
        'match_score',
        'match_reasons',
        'source',
        'is_seen',
        'is_interacted',
        'expires_at',
    ];

    protected $casts = [
        'match_score' => 'decimal:2',
        'match_reasons' => 'array',
        'is_seen' => 'boolean',
        'is_interacted' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function markAsSeen(): void
    {
        $this->update(['is_seen' => true]);
    }

    public function markAsInteracted(): void
    {
        $this->update(['is_interacted' => true]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}

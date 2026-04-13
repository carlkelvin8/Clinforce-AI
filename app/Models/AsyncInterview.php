<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AsyncInterview extends Model
{
    protected $table = 'async_interviews';

    protected $fillable = [
        'job_id', 'title', 'description', 'questions', 'max_duration_minutes',
        'allow_retries', 'expires_at', 'is_active', 'total_responses',
    ];

    protected $casts = [
        'questions' => 'array',
        'allow_retries' => 'boolean',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(AsyncResponse::class, 'async_interview_id');
    }

    /**
     * Check if a specific application has already responded.
     */
    public function hasResponseForApplication(int $applicationId): bool
    {
        return $this->responses()->where('application_id', $applicationId)->exists();
    }

    /**
     * Check if the async interview is still accepting responses.
     */
    public function isAcceptingResponses(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && now()->gt($this->expires_at)) {
            return false;
        }

        return true;
    }

    /**
     * Mark expired interviews.
     */
    public static function markExpired(): int
    {
        return static::where('is_active', true)
            ->where('expires_at', '<=', now())
            ->update(['is_active' => false]);
    }
}

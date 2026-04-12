<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerifiedSkill extends Model
{
    protected $table = 'verified_skills';

    protected $fillable = [
        'user_id',
        'skill_name',
        'assessment_id',
        'proficiency_level',
        'badge_url',
        'is_verified',
        'verified_at',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'proficiency_level' => 'integer',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'is_verified' => false,
        'proficiency_level' => 0,
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(SkillsAssessment::class, 'assessment_id');
    }

    /**
     * Scopes
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeByProficiency($query, string $direction = 'desc')
    {
        return $query->orderBy('proficiency_level', $direction);
    }

    /**
     * Helper Methods
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expires_at && $this->expires_at->diffInDays(now()) <= $days;
    }

    public function getProficiencyLabel(): string
    {
        return match (true) {
            $this->proficiency_level >= 90 => 'Expert',
            $this->proficiency_level >= 75 => 'Advanced',
            $this->proficiency_level >= 60 => 'Intermediate',
            $this->proficiency_level >= 40 => 'Basic',
            default => 'Beginner',
        };
    }

    public function getProficiencyColor(): string
    {
        return match (true) {
            $this->proficiency_level >= 90 => '#10b981',
            $this->proficiency_level >= 75 => '#3b82f6',
            $this->proficiency_level >= 60 => '#f59e0b',
            $this->proficiency_level >= 40 => '#f97316',
            default => '#6b7280',
        };
    }

    public function verify(): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

    public function markExpired(): void
    {
        $this->update(['is_verified' => false]);
    }
}

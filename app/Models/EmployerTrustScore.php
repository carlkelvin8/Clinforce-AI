<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployerTrustScore extends Model
{
    protected $table = 'employer_trust_scores';

    protected $fillable = [
        'employer_user_id', 'overall_score', 'identity_score', 'business_score',
        'rating_score', 'activity_score', 'total_reviews', 'average_rating',
        'score_breakdown', 'badges', 'red_flags', 'last_calculated_at',
    ];

    protected $casts = [
        'overall_score' => 'decimal:2',
        'identity_score' => 'decimal:2',
        'business_score' => 'decimal:2',
        'rating_score' => 'decimal:2',
        'activity_score' => 'decimal:2',
        'average_rating' => 'decimal:2',
        'score_breakdown' => 'array',
        'badges' => 'array',
        'red_flags' => 'array',
        'last_calculated_at' => 'datetime',
    ];

    const BADGE_VERIFIED_EMPLOYER = 'verified_employer';
    const BADGE_TOP_RATED = 'top_rated';
    const BADGE_RESPONSIVE = 'responsive';
    const BADGE_ACTIVE_HIRER = 'active_hirer';

    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_user_id');
    }

    public function getTrustLevelAttribute(): string
    {
        $score = $this->overall_score ?? 0;

        if ($score >= 90) return 'exceptional';
        if ($score >= 75) return 'high';
        if ($score >= 60) return 'medium';
        if ($score >= 40) return 'low';
        return 'very_low';
    }

    public function hasBadge(string $badge): bool
    {
        return in_array($badge, $this->badges ?? []);
    }

    public function addBadge(string $badge): void
    {
        $badges = $this->badges ?? [];
        if (!in_array($badge, $badges)) {
            $badges[] = $badge;
            $this->badges = $badges;
            $this->save();
        }
    }

    public function removeBadge(string $badge): void
    {
        $badges = $this->badges ?? [];
        $this->badges = array_values(array_diff($badges, [$badge]));
        $this->save();
    }

    public function addRedFlag(string $flag): void
    {
        $flags = $this->red_flags ?? [];
        if (!in_array($flag, $flags)) {
            $flags[] = $flag;
            $this->red_flags = $flags;
            $this->save();
        }
    }

    public function removeRedFlag(string $flag): void
    {
        $flags = $this->red_flags ?? [];
        $this->red_flags = array_values(array_diff($flags, [$flag]));
        $this->save();
    }

    public function scopeHighTrust($query)
    {
        return $query->where('overall_score', '>=', 75);
    }

    public function scopeLowTrust($query)
    {
        return $query->where('overall_score', '<', 40);
    }
}

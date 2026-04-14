<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrendingJob extends Model
{
    protected $fillable = [
        'job_id',
        'category',
        'region',
        'view_count',
        'application_count',
        'save_count',
        'trend_score',
        'trending_starts_at',
        'trending_ends_at',
    ];

    protected $casts = [
        'trend_score' => 'decimal:2',
        'trending_starts_at' => 'datetime',
        'trending_ends_at' => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function isCurrentlyTrending(): bool
    {
        $now = now();
        if ($this->trending_starts_at && $now->lt($this->trending_starts_at)) {
            return false;
        }
        if ($this->trending_ends_at && $now->gt($this->trending_ends_at)) {
            return false;
        }
        return true;
    }

    public function incrementViews(): void
    {
        $this->increment('view_count');
    }

    public function incrementApplications(): void
    {
        $this->increment('application_count');
    }

    public function incrementSaves(): void
    {
        $this->increment('save_count');
    }

    public function recalculateScore(): void
    {
        // Weighted scoring: applications (50%), views (30%), saves (20%)
        $score = ($this->application_count * 50) + ($this->view_count * 30) + ($this->save_count * 20);
        $this->update(['trend_score' => $score]);
    }
}

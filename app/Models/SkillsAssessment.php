<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SkillsAssessment extends Model
{
    protected $table = 'skills_assessments';

    protected $fillable = [
        'user_id',
        'template_id',
        'attempt_number',
        'score',
        'correct_answers',
        'total_questions',
        'time_taken_seconds',
        'passed',
        'answers',
        'feedback',
        'weak_areas',
        'is_verified',
        'verification_badge_url',
        'started_at',
        'completed_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'attempt_number' => 'integer',
        'score' => 'integer',
        'correct_answers' => 'integer',
        'total_questions' => 'integer',
        'time_taken_seconds' => 'integer',
        'passed' => 'boolean',
        'answers' => 'array',
        'feedback' => 'array',
        'weak_areas' => 'array',
        'is_verified' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AssessmentTemplate::class, 'template_id');
    }

    public function verifiedSkill(): HasOne
    {
        return $this->hasOne(VerifiedSkill::class, 'assessment_id');
    }

    /**
     * Scopes
     */
    public function scopePassed($query)
    {
        return $query->where('passed', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeBestAttempt($query)
    {
        return $query->orderBy('score', 'desc');
    }

    public function scopeForUserAndSkill($query, int $userId, string $skillName)
    {
        return $query->where('user_id', $userId)
            ->whereHas('template', fn($q) => $q->where('skill_name', $skillName))
            ->where('passed', true)
            ->orderBy('score', 'desc');
    }

    /**
     * Helper Methods
     */
    public function getScoreGrade(): string
    {
        return match (true) {
            $this->score >= 95 => 'A+',
            $this->score >= 90 => 'A',
            $this->score >= 85 => 'B+',
            $this->score >= 80 => 'B',
            $this->score >= 75 => 'C+',
            $this->score >= 70 => 'C',
            $this->score >= 60 => 'D',
            default => 'F',
        };
    }

    public function getScoreColor(): string
    {
        return match (true) {
            $this->score >= 90 => 'green',
            $this->score >= 70 => 'blue',
            $this->score >= 60 => 'orange',
            default => 'red',
        };
    }

    public function getTimeFormatted(): string
    {
        if (!$this->time_taken_seconds) {
            return 'N/A';
        }

        $minutes = floor($this->time_taken_seconds / 60);
        $seconds = $this->time_taken_seconds % 60;
        
        return "{$minutes}m {$seconds}s";
    }

    public function getAccuracyPercentage(): float
    {
        if ($this->total_questions === 0) {
            return 0.0;
        }

        return round(($this->correct_answers / $this->total_questions) * 100, 2);
    }

    public function isBestAttempt(): bool
    {
        $bestScore = self::where('user_id', $this->user_id)
            ->where('template_id', $this->template_id)
            ->max('score');

        return $this->score === $bestScore;
    }

    /**
     * Calculate duration
     */
    public function getDurationInMinutes(): float
    {
        if (!$this->started_at || !$this->completed_at) {
            return 0;
        }

        return round($this->started_at->diffInMinutes($this->completed_at), 2);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentTemplate extends Model
{
    protected $table = 'assessment_templates';

    protected $fillable = [
        'slug',
        'title',
        'description',
        'category',
        'skill_name',
        'duration_minutes',
        'passing_score',
        'total_questions',
        'max_attempts',
        'cooldown_hours',
        'difficulty',
        'questions',
        'is_active',
        'completions',
        'average_score',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'passing_score' => 'integer',
        'total_questions' => 'integer',
        'max_attempts' => 'integer',
        'cooldown_hours' => 'integer',
        'questions' => 'array',
        'is_active' => 'boolean',
        'completions' => 'integer',
        'average_score' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'duration_minutes' => 30,
        'passing_score' => 70,
        'max_attempts' => 3,
        'cooldown_hours' => 24,
        'difficulty' => 'intermediate',
        'is_active' => true,
        'completions' => 0,
    ];

    /**
     * Relationships
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(SkillsAssessment::class, 'template_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    public function scopeBySkill($query, string $skill)
    {
        return $query->where('skill_name', 'like', "%{$skill}%");
    }

    /**
     * Helper Methods
     */
    public function getDifficultyLabel(): string
    {
        return match ($this->difficulty) {
            'beginner' => 'Beginner',
            'intermediate' => 'Intermediate',
            'advanced' => 'Advanced',
            'expert' => 'Expert',
            default => 'Intermediate',
        };
    }

    public function getDifficultyColor(): string
    {
        return match ($this->difficulty) {
            'beginner' => 'green',
            'intermediate' => 'blue',
            'advanced' => 'orange',
            'expert' => 'red',
            default => 'gray',
        };
    }

    public function canUserAttempt(User $user): array
    {
        $userAttempts = $this->assessments()
            ->where('user_id', $user->id)
            ->count();

        if ($userAttempts >= $this->max_attempts) {
            return [
                'can_attempt' => false,
                'reason' => "Maximum attempts ({$this->max_attempts}) reached",
            ];
        }

        $lastAttempt = $this->assessments()
            ->where('user_id', $user->id)
            ->latest('completed_at')
            ->first();

        if ($lastAttempt && $lastAttempt->completed_at) {
            $cooldownEnds = $lastAttempt->completed_at->copy()->addHours($this->cooldown_hours);
            if (now()->lessThan($cooldownEnds)) {
                return [
                    'can_attempt' => false,
                    'reason' => "Cooldown active. Try again after {$cooldownEnds->diffForHumans()}",
                    'cooldown_ends_at' => $cooldownEnds,
                ];
            }
        }

        return [
            'can_attempt' => true,
            'next_attempt_number' => $userAttempts + 1,
        ];
    }

    public function incrementCompletions(): void
    {
        $this->increment('completions');
        $this->updateAverageScore();
    }

    private function updateAverageScore(): void
    {
        $avgScore = $this->assessments()
            ->where('passed', true)
            ->avg('score');
        
        $this->update(['average_score' => $avgScore]);
    }
}

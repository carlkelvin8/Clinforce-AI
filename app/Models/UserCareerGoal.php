<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCareerGoal extends Model
{
    protected $fillable = [
        'user_id',
        'career_path_id',
        'target_role',
        'goals',
        'completed_steps',
        'target_completion_date',
    ];

    protected $casts = [
        'goals' => 'array',
        'completed_steps' => 'array',
        'target_completion_date' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function careerPath(): BelongsTo
    {
        return $this->belongsTo(CareerPath::class);
    }

    public function getProgressPercentageAttribute(): ?float
    {
        if (empty($this->goals)) {
            return null;
        }

        $completed = count($this->completed_steps ?? []);
        $total = count($this->goals);

        return $total > 0 ? round(($completed / $total) * 100, 2) : 0;
    }
}

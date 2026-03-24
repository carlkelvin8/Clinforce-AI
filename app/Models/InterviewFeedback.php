<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewFeedback extends Model
{
    protected $table = 'interview_feedback';

    protected $fillable = [
        'interview_id',
        'submitted_by_user_id',
        'rating',
        'notes',
        'technical_score',
        'communication_score',
        'culture_fit_score',
        'recommendation',
    ];

    protected $casts = [
        'rating' => 'integer',
        'technical_score' => 'integer',
        'communication_score' => 'integer',
        'culture_fit_score' => 'integer',
    ];

    public function interview(): BelongsTo
    {
        return $this->belongsTo(Interview::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }
}

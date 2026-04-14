<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployerInterviewReview extends Model
{
    protected $table = 'employer_interview_reviews';

    protected $fillable = [
        'employer_user_id', 'applicant_user_id', 'job_id', 'interview_id',
        'overall_rating', 'professionalism_rating', 'communication_rating',
        'transparency_rating', 'comments', 'tags', 'would_recommend',
        'is_anonymous', 'status', 'ip_address',
    ];

    protected $casts = [
        'overall_rating' => 'decimal:2',
        'professionalism_rating' => 'decimal:2',
        'communication_rating' => 'decimal:2',
        'transparency_rating' => 'decimal:2',
        'would_recommend' => 'boolean',
        'is_anonymous' => 'boolean',
        'tags' => 'array',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_user_id');
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_user_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function interview(): BelongsTo
    {
        return $this->belongsTo(Interview::class, 'interview_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePositive($query)
    {
        return $query->where('overall_rating', '>=', 4);
    }

    public function scopeNegative($query)
    {
        return $query->where('overall_rating', '<=', 2);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModerationQueue extends Model
{
    protected $table = 'moderation_queue';

    protected $fillable = [
        'moderable_type', 'moderable_id', 'reported_by_user_id', 'flagged_by_system',
        'flag_reason', 'description', 'context_data', 'ai_analysis', 'priority',
        'status', 'assigned_to_user_id', 'reviewed_by_user_id', 'moderator_notes',
        'action_taken', 'reviewed_at', 'action_taken_at',
    ];

    protected $casts = [
        'context_data' => 'array',
        'ai_analysis' => 'array',
        'action_taken' => 'array',
        'reviewed_at' => 'datetime',
        'action_taken_at' => 'datetime',
    ];

    const FLAG_AUTO_FLAGGED = 'auto_flagged';
    const FLAG_USER_REPORTED = 'user_reported';
    const FLAG_AI_SUSPICIOUS = 'ai_suspicious';
    const FLAG_KEYWORD_MATCH = 'keyword_match';

    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    const STATUS_QUEUED = 'queued';
    const STATUS_IN_REVIEW = 'in_review';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_ESCALATED = 'escalated';

    public function moderatable(): MorphTo
    {
        return $this->morphTo();
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function flaggedBySystem(): BelongsTo
    {
        return $this->belongsTo(User::class, 'flagged_by_system');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ModerationAction::class, 'moderation_queue_id');
    }

    public function scopeQueued($query)
    {
        return $query->where('status', self::STATUS_QUEUED);
    }

    public function scopeInReview($query)
    {
        return $query->where('status', self::STATUS_IN_REVIEW);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    public function scopeNeedsAttention($query)
    {
        return $query->whereIn('status', [self::STATUS_QUEUED, self::STATUS_IN_REVIEW])
                     ->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }
}

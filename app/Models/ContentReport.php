<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentReport extends Model
{
    protected $table = 'content_reports';

    protected $fillable = [
        'reported_by_user_id', 'reportable_type', 'reportable_id', 'reported_user_id',
        'reason', 'description', 'evidence', 'severity', 'status',
        'assigned_to_user_id', 'resolved_by_user_id', 'resolution_notes',
        'action_taken', 'resolved_at',
    ];

    protected $casts = [
        'evidence' => 'array',
        'action_taken' => 'array',
        'resolved_at' => 'datetime',
    ];

    const REASON_INAPPROPRIATE = 'inappropriate';
    const REASON_SPAM = 'spam';
    const REASON_SCAM = 'scam';
    const REASON_HARASSMENT = 'harassment';
    const REASON_DISCRIMINATION = 'discrimination';
    const REASON_FAKE = 'fake';
    const REASON_OFFENSIVE = 'offensive';
    const REASON_OTHER = 'other';

    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';

    const STATUS_PENDING = 'pending';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_ACTION_TAKEN = 'action_taken';
    const STATUS_DISMISSED = 'dismissed';
    const STATUS_ESCALATED = 'escalated';

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', self::STATUS_UNDER_REVIEW);
    }

    public function scopeHighSeverity($query)
    {
        return $query->whereIn('severity', [self::SEVERITY_HIGH, self::SEVERITY_CRITICAL]);
    }

    public function scopeResolved($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTION_TAKEN, self::STATUS_DISMISSED]);
    }
}

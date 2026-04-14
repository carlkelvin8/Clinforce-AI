<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployerRedFlag extends Model
{
    protected $table = 'employer_red_flags';

    protected $fillable = [
        'employer_user_id', 'flag_type', 'description', 'evidence',
        'reported_by_user_id', 'investigated_by_user_id', 'severity',
        'status', 'resolution_notes', 'resolved_at',
    ];

    protected $casts = [
        'evidence' => 'array',
        'resolved_at' => 'datetime',
    ];

    const TYPE_FAKE_JOB = 'fake_job';
    const TYPE_SCAM = 'scam';
    const TYPE_HARASSMENT = 'harassment';
    const TYPE_DISCRIMINATION = 'discrimination';
    const TYPE_NO_SHOW = 'no_show';
    const TYPE_PAYMENT_ISSUE = 'payment_issue';

    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';

    const STATUS_REPORTED = 'reported';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_DISMISSED = 'dismissed';

    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_user_id');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function investigator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investigated_by_user_id');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_REPORTED, self::STATUS_UNDER_REVIEW, self::STATUS_CONFIRMED]);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeHighSeverity($query)
    {
        return $query->whereIn('severity', [self::SEVERITY_HIGH, self::SEVERITY_CRITICAL]);
    }
}

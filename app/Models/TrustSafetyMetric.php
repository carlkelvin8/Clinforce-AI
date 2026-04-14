<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrustSafetyMetric extends Model
{
    protected $table = 'trust_safety_metrics';

    protected $fillable = [
        'metric_date', 'metric_type', 'count', 'breakdown',
    ];

    protected $casts = [
        'metric_date' => 'date',
        'breakdown' => 'array',
    ];

    const TYPE_REPORTS_RECEIVED = 'reports_received';
    const TYPE_REPORTS_RESOLVED = 'reports_resolved';
    const TYPE_FRAUD_DETECTED = 'fraud_detected';
    const TYPE_VERIFICATIONS_COMPLETED = 'verifications_completed';
    const TYPE_VERIFICATIONS_PENDING = 'verifications_pending';
    const TYPE_BACKGROUND_CHECKS_COMPLETED = 'background_checks_completed';
    const TYPE_MODERATION_QUEUE_SIZE = 'moderation_queue_size';
    const TYPE_CONTENT_REMOVED = 'content_removed';
    const TYPE_USERS_SUSPENDED = 'users_suspended';
    const TYPE_USERS_BANNED = 'users_banned';

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('metric_date', [$startDate, $endDate]);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('metric_type', $type);
    }
}

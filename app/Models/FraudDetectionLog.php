<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FraudDetectionLog extends Model
{
    protected $table = 'fraud_detection_logs';

    protected $fillable = [
        'user_id', 'fraud_type', 'severity', 'description', 'evidence_data',
        'related_entity_type', 'related_entity_id', 'status',
        'investigated_by_user_id', 'investigation_notes', 'investigated_at',
    ];

    protected $casts = [
        'evidence_data' => 'array',
        'investigated_at' => 'datetime',
    ];

    const TYPE_DUPLICATE_ACCOUNT = 'duplicate_account';
    const TYPE_FAKE_CREDENTIALS = 'fake_credentials';
    const TYPE_STOLEN_IDENTITY = 'stolen_identity';
    const TYPE_SUSPICIOUS_ACTIVITY = 'suspicious_activity';

    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';

    const STATUS_PENDING = 'pending';
    const STATUS_INVESTIGATING = 'investigating';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_FALSE_POSITIVE = 'false_positive';
    const STATUS_RESOLVED = 'resolved';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function investigator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investigated_by_user_id');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_INVESTIGATING]);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }
}

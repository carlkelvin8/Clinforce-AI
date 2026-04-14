<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdentityVerification extends Model
{
    protected $table = 'identity_verifications';

    protected $fillable = [
        'user_id', 'document_type', 'document_number', 'document_front_url',
        'document_back_url', 'video_selfie_url', 'extracted_name', 'extracted_dob',
        'extracted_expiry', 'verification_status', 'ai_verification_data',
        'confidence_score', 'rejection_reason', 'verified_at', 'expires_at',
        'ip_address', 'user_agent',
    ];

    protected $casts = [
        'ai_verification_data' => 'array',
        'confidence_score' => 'float',
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_VERIFIED = 'verified';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EXPIRED = 'expired';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isVerified(): bool
    {
        return $this->verification_status === self::STATUS_VERIFIED &&
               (!$this->expires_at || $this->expires_at->isFuture());
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function scopeVerified($query)
    {
        return $query->where('verification_status', self::STATUS_VERIFIED);
    }

    public function scopePending($query)
    {
        return $query->where('verification_status', self::STATUS_PENDING);
    }
}

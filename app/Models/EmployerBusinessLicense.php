<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployerBusinessLicense extends Model
{
    protected $table = 'employer_business_licenses';

    protected $fillable = [
        'employer_user_id', 'license_type', 'license_number', 'document_url',
        'issuing_authority', 'issued_date', 'expiry_date', 'verification_status',
        'verification_data', 'rejection_reason', 'verified_by_user_id', 'verified_at',
    ];

    protected $casts = [
        'verification_data' => 'array',
        'issued_date' => 'date',
        'expiry_date' => 'date',
        'verified_at' => 'datetime',
    ];

    const TYPE_BUSINESS_REGISTRATION = 'business_registration';
    const TYPE_TAX_CERTIFICATE = 'tax_certificate';
    const TYPE_INDUSTRY_LICENSE = 'industry_license';

    const STATUS_PENDING = 'pending';
    const STATUS_VERIFIED = 'verified';
    const STATUS_EXPIRED = 'expired';
    const STATUS_REJECTED = 'rejected';

    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_user_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function isVerified(): bool
    {
        return $this->verification_status === self::STATUS_VERIFIED &&
               (!$this->expiry_date || $this->expiry_date->isFuture());
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function scopeVerified($query)
    {
        return $query->where('verification_status', self::STATUS_VERIFIED);
    }
}

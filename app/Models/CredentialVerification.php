<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CredentialVerification extends Model
{
    protected $table = 'credential_verifications';

    protected $fillable = [
        'applicant_user_id', 'requested_by_user_id', 'credential_type',
        'license_number', 'issuing_authority', 'country', 'state_province',
        'issued_date', 'expiry_date', 'verification_url', 'method',
        'status', 'document_url', 'verification_data', 'notes',
        'verified_at', 'last_checked_at',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'expiry_date' => 'date',
        'verification_data' => 'array',
        'verified_at' => 'datetime',
        'last_checked_at' => 'datetime',
    ];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_user_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    /**
     * Check if the credential is currently valid.
     */
    public function isValid(): bool
    {
        if ($this->status === 'invalid') {
            return false;
        }

        if ($this->expiry_date && now()->gt($this->expiry_date)) {
            return false;
        }

        return $this->status === 'verified';
    }

    /**
     * Check if expiring soon (within given days).
     */
    public function isExpiringSoon(int $withinDays = 30): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        return $this->expiry_date->lte(now()->addDays($withinDays))
            && $this->expiry_date->gt(now());
    }

    /**
     * Mark as expired.
     */
    public function markExpired(): void
    {
        $this->update([
            'status' => 'expired',
            'last_checked_at' => now(),
        ]);
    }

    /**
     * Mark as verified.
     */
    public function markVerified(array $data = []): void
    {
        $this->update(array_merge([
            'status' => 'verified',
            'verified_at' => now(),
            'last_checked_at' => now(),
        ], $data));
    }

    /**
     * Mark as invalid.
     */
    public function markInvalid(string $notes = ''): void
    {
        $this->update([
            'status' => 'invalid',
            'notes' => $notes ?: $this->notes,
            'last_checked_at' => now(),
        ]);
    }

    /**
     * Common healthcare credential types.
     */
    public static function commonTypes(): array
    {
        return [
            'PRC License' => 'Professional Regulation Commission License',
            'RN License' => 'Registered Nurse License',
            'BLS' => 'Basic Life Support Certification',
            'ACLS' => 'Advanced Cardiac Life Support',
            'PALS' => 'Pediatric Advanced Life Support',
            'NRP' => 'Neonatal Resuscitation Program',
            'CPR' => 'CPR Certification',
            'First Aid' => 'First Aid Certification',
            'NCLEX' => 'NCLEX Registration',
            'DEA' => 'DEA Registration (US)',
            'Board Certification' => 'Medical Board Certification',
            'Other' => 'Other Credential',
        ];
    }

    /**
     * Scopes: expiring within N days.
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', 'verified')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>', now());
    }

    /**
     * Scopes: currently valid credentials.
     */
    public function scopeValid($query)
    {
        return $query->where('status', 'verified')
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>', now());
            });
    }
}

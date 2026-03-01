<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAccessPayment extends Model
{
    protected $table = 'document_access_payments';

    protected $fillable = [
        'employer_user_id',
        'applicant_user_id',
        'application_id',
        'access_type',
        'amount_cents',
        'currency_code',
        'status',
        'provider',
        'provider_ref',
        'paid_at',
        'expires_at',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_user_id');
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_user_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'application_id');
    }

    public static function hasAccess(int $employerId, int $applicantId): bool
    {
        return self::query()
            ->where('employer_user_id', $employerId)
            ->where('applicant_user_id', $applicantId)
            ->where('status', 'paid')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ReferenceCheck extends Model
{
    protected $table = 'reference_checks';

    protected $fillable = [
        'application_id', 'requested_by_user_id', 'referee_name', 'referee_email',
        'referee_phone', 'referee_relationship', 'referee_title', 'referee_company',
        'questions', 'responses', 'status', 'comments', 'rating', 'would_rehire',
        'sent_at', 'completed_at', 'expires_at', 'token', 'reminder_count', 'last_reminder_at',
    ];

    protected $casts = [
        'questions' => 'array',
        'responses' => 'array',
        'rating' => 'float',
        'would_rehire' => 'boolean',
        'sent_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_reminder_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ReferenceCheck $check) {
            if (empty($check->token)) {
                $check->token = Str::random(64);
            }
            if (empty($check->expires_at)) {
                $check->expires_at = now()->addDays(7);
            }
        });
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'application_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    /**
     * Check if the token matches.
     */
    public function isValidToken(string $token): bool
    {
        return hash_equals($this->token, $token);
    }

    /**
     * Check if this reference request is still actionable.
     */
    public function isActionable(): bool
    {
        if (in_array($this->status, ['completed', 'bounced'], true)) {
            return false;
        }

        if ($this->expires_at && now()->gt($this->expires_at)) {
            return true; // still valid but expired
        }

        return true;
    }

    /**
     * Mark as expired.
     */
    public function markExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    /**
     * Default reference questions if none provided.
     */
    public static function defaultQuestions(): array
    {
        return [
            [
                'question' => 'What was your working relationship with the candidate?',
                'type' => 'text',
            ],
            [
                'question' => 'How would you rate their overall job performance?',
                'type' => 'rating',
                'scale' => '1-5',
            ],
            [
                'question' => 'What are their key strengths?',
                'type' => 'text',
            ],
            [
                'question' => 'Are there any areas where they could improve?',
                'type' => 'text',
            ],
            [
                'question' => 'Would you rehire this candidate?',
                'type' => 'yes_no',
            ],
            [
                'question' => 'Any additional comments?',
                'type' => 'text',
            ],
        ];
    }
}

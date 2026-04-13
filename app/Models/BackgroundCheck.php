<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackgroundCheck extends Model
{
    protected $table = 'background_checks';

    protected $fillable = [
        'application_id', 'requested_by_user_id', 'assigned_to_user_id',
        'provider', 'provider_reference_id', 'type', 'status', 'result',
        'report_data', 'summary', 'notes', 'initiated_at', 'completed_at',
        'expires_at', 'candidate_consent', 'consent_given_at',
    ];

    protected $casts = [
        'report_data' => 'array',
        'candidate_consent' => 'boolean',
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'consent_given_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'application_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Available background check providers.
     * Set provider configuration in .env: BACKGROUND_CHECK_PROVIDER, BACKGROUND_CHECK_API_KEY
     */
    public static function availableProviders(): array
    {
        return [
            'manual' => 'Manual Review',
            // Add real providers when API keys are configured:
            // 'checkr'   => 'Checkr',
            // 'goodhire' => 'GoodHire',
            // 'sterling' => 'Sterling',
        ];
    }

    /**
     * Available background check types.
     */
    public static function availableTypes(): array
    {
        return [
            'criminal' => 'Criminal Record Check',
            'employment' => 'Employment History Verification',
            'education' => 'Education Verification',
            'drug' => 'Drug Screening',
            'credit' => 'Credit Check',
            'comprehensive' => 'Comprehensive Background',
            'manual' => 'Manual Review',
        ];
    }

    /**
     * Check if the check is complete.
     */
    public function isComplete(): bool
    {
        return in_array($this->status, ['completed', 'flagged'], true);
    }

    /**
     * Human-readable result label.
     */
    public function resultLabel(): string
    {
        return match ($this->result) {
            'clear' => 'Clear — No issues found',
            'flagged' => 'Flagged — Review required',
            'inconclusive' => 'Inconclusive — Further investigation needed',
            'failed' => 'Failed — Disqualifying findings',
            default => 'Pending',
        };
    }
}

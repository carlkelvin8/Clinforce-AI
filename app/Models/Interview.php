<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Interview extends Model
{
    protected $table = 'interviews';

    protected $fillable = [
        'application_id',
        'scheduled_start',
        'scheduled_end',
        'mode',
        'meeting_provider',
        'provider_meeting_id',
        'provider_join_url',
        'provider_start_url',
        'provider_payload',
        'meeting_link',
        'location_text',
        'status',
        'cancel_reason',
        'created_by_user_id',
        'reminder_sent_at',
    ];

    protected $casts = [
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'application_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the applicant user via the application relationship.
     * (interviews table has no applicant_user_id column)
     */
    public function getApplicantAttribute(): ?User
    {
        return $this->application?->applicant;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class JobApplication extends Model
{
    protected $table = 'job_applications';

    protected $fillable = [
        'job_id',
        'applicant_user_id',
        'status',
        'cover_letter',
        'resume_document_id',
        'cover_letter_document_id',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['applicant_name', 'applicant_email'];

    public function getApplicantNameAttribute(): string
    {
        return $this->applicant?->name ?? 'Unknown';
    }

    public function getApplicantEmailAttribute(): ?string
    {
        return $this->applicant?->email;
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_user_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class, 'application_id');
    }

    public function interview(): HasOne
    {
        return $this->hasOne(Interview::class, 'application_id');
    }

    public function aiScreenings(): HasMany
    {
        return $this->hasMany(AiScreening::class, 'application_id');
    }

    public function screeningAnswers(): HasMany
    {
        return $this->hasMany(ScreeningAnswer::class, 'application_id');
    }

    public function asyncResponses(): HasMany
    {
        return $this->hasMany(AsyncResponse::class, 'application_id');
    }

    public function referenceChecks(): HasMany
    {
        return $this->hasMany(ReferenceCheck::class, 'application_id');
    }

    public function backgroundChecks(): HasMany
    {
        return $this->hasMany(BackgroundCheck::class, 'application_id');
    }

    /**
     * Check if any knockout questions were triggered for this application.
     */
    public function hasKnockouts(): bool
    {
        return $this->screeningAnswers()->where('knockout_triggered', true)->exists();
    }
}

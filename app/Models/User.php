<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasApiTokens, Notifiable, HasFactory, MustVerifyEmailTrait;

    protected $table = 'users';

    protected $fillable = [
        'role',
        'email',
        'phone',
        'password_hash',
        'stripe_customer_id',
        'status',
        'email_verified_at',
        'last_login_at',
        'trial_started_at',
        'trial_ends_at',
        'trial_consumed',
        'trial_activated_ip',
        'trial_activated_user_agent',
        'trial_device_hash',
        'subscription_status',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'trial_started_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'trial_consumed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Make Laravel treat password_hash as the password field.
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * Check if user is currently on an active trial.
     */
    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if user has an expired trial.
     */
    public function hasExpiredTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    protected $appends = ['avatar_url', 'name'];

    public function getNameAttribute(): string
    {
        if ($this->role === 'applicant') {
            $profile = $this->applicantProfile;
            if ($profile) {
                return trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? '')) ?: ($profile->public_display_name ?: ($this->email ?? 'Candidate'));
            }
        } elseif ($this->role === 'employer') {
            $profile = $this->employerProfile;
            if ($profile) {
                return $profile->business_name ?: ($this->email ?? 'Employer');
            }
        } elseif ($this->role === 'agency') {
            $profile = $this->agencyProfile;
            if ($profile) {
                return $profile->agency_name ?: ($this->email ?? 'Agency');
            }
        }

        return $this->email ?? 'User';
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->role === 'employer') {
            return asset('assets/brand/default-hospital-logo.svg');
        }

        $path = null;
        if ($this->role === 'applicant') {
            $path = $this->applicantProfile?->avatar;
        } elseif ($this->role === 'agency') {
            $path = $this->agencyProfile?->logo;
        }

        if (!$path) {
            return null;
        }

        $p = ltrim($path, '/');

        if (str_starts_with($p, 'http://') || str_starts_with($p, 'https://')) {
            return $p;
        }

        // Handle cases where the path might already contain storage/
        if (str_starts_with($p, 'storage/')) {
            return asset($p);
        }

        // Handle uploads/avatars/ or uploads/
        if (str_contains($p, 'uploads/avatars/')) {
            $pos = strpos($p, 'uploads/avatars/');
            return asset(substr($p, $pos));
        }

        if (str_starts_with($p, 'uploads/')) {
            return asset($p);
        }

        // Default to storage disk path
        return asset('storage/' . $p);
    }

    // Profiles
    public function employerProfile(): HasOne
    {
        return $this->hasOne(EmployerProfile::class, 'user_id');
    }

    public function agencyProfile(): HasOne
    {
        return $this->hasOne(AgencyProfile::class, 'user_id');
    }

    public function applicantProfile(): HasOne
    {
        return $this->hasOne(ApplicantProfile::class, 'user_id');
    }

    // Documents
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'user_id');
    }

    // Subscriptions / Payments
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'user_id');
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class, 'user_id')
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('current_period_end')
                    ->orWhere('current_period_end', '>', now());
            })
            ->latest();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'user_id');
    }

    // Jobs owned (filter by owner_type in queries)
    public function jobsOwned(): HasMany
    {
        return $this->hasMany(Job::class, 'owner_user_id');
    }

    // Applications (as applicant)
    public function jobApplications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'applicant_user_id');
    }

    // Admin / Verification / Audit
    public function verificationRequests(): HasMany
    {
        return $this->hasMany(VerificationRequest::class, 'user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_user_id');
    }
}

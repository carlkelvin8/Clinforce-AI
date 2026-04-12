<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ApplicantProfile extends Model
{
    protected $table = 'applicant_profiles';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'headline',
        'summary',
        'years_experience',
        'country',
        'state',
        'city',
        'public_display_name',
        'avatar',
        'skills',
        'work_experience',
        'education',
        'portfolio_links',
        'open_to_work',
        // Profile Enhancement Fields
        'video_intro_url',
        'video_intro_thumbnail',
        'video_intro_duration',
        'available_from',
        'notice_period',
        'available_for_contract',
        'available_for_fulltime',
        'available_for_parttime',
        'available_for_freelance',
        'ai_resume',
        'ai_resume_generated',
        'ai_resume_generated_at',
        'certifications',
        'languages',
        'work_preferences',
        'profile_completeness',
        'profile_badges',
    ];

    protected $casts = [
        'years_experience' => 'integer',
        'skills' => 'array',
        'work_experience' => 'array',
        'education' => 'array',
        'portfolio_links' => 'array',
        'open_to_work' => 'boolean',
        'available_from' => 'date',
        'available_for_contract' => 'boolean',
        'available_for_fulltime' => 'boolean',
        'available_for_parttime' => 'boolean',
        'available_for_freelance' => 'boolean',
        'ai_resume' => 'array',
        'ai_resume_generated' => 'boolean',
        'ai_resume_generated_at' => 'datetime',
        'certifications' => 'array',
        'languages' => 'array',
        'work_preferences' => 'array',
        'profile_completeness' => 'integer',
        'profile_badges' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'open_to_work' => false,
        'available_for_contract' => true,
        'available_for_fulltime' => true,
        'available_for_parttime' => false,
        'available_for_freelance' => false,
        'ai_resume_generated' => false,
        'profile_completeness' => 0,
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Profile Enhancement Relationships
    public function portfolios(): HasMany
    {
        return $this->hasMany(Portfolio::class, 'user_id')->orderBy('display_order');
    }

    public function featuredPortfolios(): HasMany
    {
        return $this->hasMany(Portfolio::class, 'user_id')
                    ->where('is_featured', true)
                    ->orderBy('display_order');
    }

    public function skillsAssessments(): HasMany
    {
        return $this->hasMany(SkillsAssessment::class, 'user_id');
    }

    public function passedAssessments(): HasMany
    {
        return $this->hasMany(SkillsAssessment::class, 'user_id')
                    ->where('passed', true);
    }

    public function verifiedSkills(): HasMany
    {
        return $this->hasMany(VerifiedSkill::class, 'user_id');
    }

    public function endorsementsReceived(): HasMany
    {
        return $this->hasMany(Endorsement::class, 'recipient_user_id')
                    ->where('is_hidden', false);
    }

    public function endorsementsGiven(): HasMany
    {
        return $this->hasMany(Endorsement::class, 'endorser_user_id');
    }

    /**
     * Profile Enhancement Methods
     */
    public function calculateProfileCompleteness(): int
    {
        $score = 0;
        $total = 100;

        // Basic Info (25 points)
        if ($this->first_name && $this->last_name) $score += 5;
        if ($this->headline) $score += 5;
        if ($this->summary && strlen($this->summary) > 50) $score += 10;
        if ($this->avatar) $score += 5;

        // Experience & Education (20 points)
        if ($this->work_experience && count($this->work_experience) > 0) $score += 10;
        if ($this->education && count($this->education) > 0) $score += 10;

        // Skills (15 points)
        if ($this->skills && count($this->skills) > 0) $score += 15;

        // Video Intro (10 points)
        if ($this->video_intro_url) $score += 10;

        // Availability (10 points)
        if ($this->available_from) $score += 5;
        if ($this->notice_period) $score += 5;

        // Portfolio (10 points)
        if ($this->portfolios()->count() > 0) $score += 10;

        // Verified Skills (10 points)
        if ($this->verifiedSkills()->count() > 0) $score += 10;

        $this->update(['profile_completeness' => $score]);
        
        return $score;
    }

    public function getAvailabilityStatus(): array
    {
        return [
            'available_from' => $this->available_from?->format('Y-m-d'),
            'notice_period' => $this->notice_period,
            'is_immediately_available' => $this->notice_period === 'immediate',
            'available_for' => array_filter([
                'fulltime' => $this->available_for_fulltime,
                'parttime' => $this->available_for_parttime,
                'contract' => $this->available_for_contract,
                'freelance' => $this->available_for_freelance,
            ]),
        ];
    }

    public function getTopSkills(int $limit = 10): array
    {
        // Combine self-reported skills with verified skills
        $selfReported = $this->skills ?? [];
        $verified = $this->verifiedSkills()
            ->verified()
            ->active()
            ->orderBy('proficiency_level', 'desc')
            ->get()
            ->map(fn($s) => [
                'name' => $s->skill_name,
                'proficiency' => $s->proficiency_level,
                'verified' => true,
                'badge_url' => $s->badge_url,
            ])
            ->toArray();

        // Mark self-reported skills that aren't verified
        $verifiedSkillNames = array_column($verified, 'name');
        $selfReportedEnhanced = collect($selfReported)
            ->filter(fn($skill) => !in_array(is_string($skill) ? $skill : ($skill['name'] ?? ''), $verifiedSkillNames))
            ->take($limit)
            ->map(fn($skill) => is_string($skill) 
                ? ['name' => $skill, 'proficiency' => null, 'verified' => false]
                : $skill
            )
            ->toArray();

        return array_merge($verified, $selfReportedEnhanced);
    }
}

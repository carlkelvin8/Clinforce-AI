<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Endorsement extends Model
{
    protected $table = 'endorsements';

    protected $fillable = [
        'recipient_user_id',
        'endorser_user_id',
        'type',
        'skill_name',
        'message',
        'rating',
        'relationship',
        'company_name',
        'start_date',
        'end_date',
        'is_verified',
        'verified_at',
        'is_hidden',
        'helpful_count',
        'endorsed_by_employers',
    ];

    protected $casts = [
        'rating' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'is_hidden' => 'boolean',
        'helpful_count' => 'integer',
        'endorsed_by_employers' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'type' => 'skill',
        'is_verified' => false,
        'is_hidden' => false,
        'helpful_count' => 0,
    ];

    /**
     * Relationships
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function endorser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'endorser_user_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(EndorsementVote::class, 'endorsement_id');
    }

    /**
     * Scopes
     */
    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForSkill($query, string $skill)
    {
        return $query->where('type', 'skill')
                     ->where('skill_name', 'like', "%{$skill}%");
    }

    public function scopeMostHelpful($query)
    {
        return $query->orderBy('helpful_count', 'desc');
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Helper Methods
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'skill' => 'Skill Endorsement',
            'recommendation' => 'Recommendation',
            'character' => 'Character Reference',
            'work_ethic' => 'Work Ethic',
            'leadership' => 'Leadership',
            default => 'Endorsement',
        };
    }

    public function getTypeIcon(): string
    {
        return match ($this->type) {
            'skill' => '⭐',
            'recommendation' => '📝',
            'character' => '🤝',
            'work_ethic' => '💪',
            'leadership' => '👑',
            default => '✓',
        };
    }

    public function getRelationshipLabel(): string
    {
        return $this->relationship ?? 'Professional Connection';
    }

    public function isFromEmployer(): bool
    {
        return $this->endorser->role === 'employer' || $this->endorser->role === 'admin';
    }

    public function isFromColleague(): bool
    {
        return $this->endorser->role === 'applicant';
    }

    public function markAsHelpful(int $userId): bool
    {
        $existingVote = EndorsementVote::where('endorsement_id', $this->id)
            ->where('user_id', $userId)
            ->first();

        if ($existingVote) {
            return false; // Already voted
        }

        EndorsementVote::create([
            'endorsement_id' => $this->id,
            'user_id' => $userId,
            'is_helpful' => true,
        ]);

        $this->increment('helpful_count');
        return true;
    }

    public function verify(): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }

    public function hide(): void
    {
        $this->update(['is_hidden' => true]);
    }

    public function show(): void
    {
        $this->update(['is_hidden' => false]);
    }

    /**
     * Check if endorsement is from verified colleague
     */
    public function checkVerification(): bool
    {
        // Check if endorser and recipient worked at same company
        if (!$this->company_name) {
            return false;
        }

        $recipientEmployer = $this->recipient->employerProfile;
        if (!$recipientEmployer || $recipientEmployer->business_name !== $this->company_name) {
            return false;
        }

        $endorserEmployer = $this->endorser->employerProfile;
        if ($endorserEmployer && $endorserEmployer->business_name === $this->company_name) {
            return true;
        }

        return false;
    }
}

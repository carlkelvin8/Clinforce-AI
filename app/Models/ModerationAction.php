<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModerationAction extends Model
{
    protected $table = 'moderation_actions';

    protected $fillable = [
        'moderation_queue_id', 'taken_by_user_id', 'action_type', 'target_type',
        'target_id', 'action_data', 'reason', 'expires_at',
    ];

    protected $casts = [
        'action_data' => 'array',
        'expires_at' => 'datetime',
    ];

    const TYPE_WARNING = 'warning';
    const TYPE_CONTENT_REMOVAL = 'content_removal';
    const TYPE_TEMPORARY_SUSPENSION = 'temporary_suspension';
    const TYPE_PERMANENT_BAN = 'permanent_ban';
    const TYPE_ACCOUNT_RESTRICTION = 'account_restriction';

    public function moderationQueue(): BelongsTo
    {
        return $this->belongsTo(ModerationQueue::class, 'moderation_queue_id');
    }

    public function takenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'taken_by_user_id');
    }

    public function isActive(): bool
    {
        return !$this->expires_at || $this->expires_at->isFuture();
    }

    public function scopeActive($query)
    {
        return $query->whereNull('expires_at')
                     ->orWhere('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
                     ->where('expires_at', '<=', now());
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobSwipe extends Model
{
    protected $fillable = [
        'user_id',
        'job_id',
        'action',
        'reason',
        'match_score',
    ];

    protected $casts = [
        'match_score' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function isRightSwipe(): bool
    {
        return in_array($this->action, ['right', 'super'], true);
    }

    public function isSuperSwipe(): bool
    {
        return $this->action === 'super';
    }
}

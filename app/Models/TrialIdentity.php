<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrialIdentity extends Model
{
    protected $table = 'trial_identities';

    protected $fillable = [
        'identity_type',
        'identity_hash',
        'first_user_id',
        'first_seen_at',
        'trial_consumed_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'first_user_id' => 'integer',
        'first_seen_at' => 'datetime',
        'trial_consumed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function firstUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'first_user_id');
    }
}


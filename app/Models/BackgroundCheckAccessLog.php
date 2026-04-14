<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackgroundCheckAccessLog extends Model
{
    protected $table = 'background_check_access_logs';

    protected $fillable = [
        'background_check_id', 'accessed_by_user_id', 'action', 'ip_address', 'user_agent',
    ];

    public function backgroundCheck(): BelongsTo
    {
        return $this->belongsTo(BackgroundCheck::class, 'background_check_id');
    }

    public function accessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accessed_by_user_id');
    }

    public function scopeRecentAccess($query, $minutes = 60)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }
}

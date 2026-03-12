<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZoomFilterSetting extends Model
{
    protected $table = 'zoom_filter_settings';

    protected $fillable = [
        'user_id',
        'filter_emails',
        'filter_domains',
        'blocked_domains',
        'custom_patterns',
        'replacement_text',
        'monitor_audio',
        'lock_name',
        'privacy_filtering',
    ];

    protected $casts = [
        'filter_emails' => 'boolean',
        'filter_domains' => 'boolean',
        'monitor_audio' => 'boolean',
        'lock_name' => 'boolean',
        'privacy_filtering' => 'boolean',
        'blocked_domains' => 'array',
        'custom_patterns' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

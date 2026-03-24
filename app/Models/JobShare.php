<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class JobShare extends Model
{
    public $timestamps = false;

    protected $fillable = ['job_id', 'shared_by_user_id', 'share_token', 'clicks'];

    protected $casts = ['created_at' => 'datetime'];

    public static function generateToken(): string
    {
        do {
            $token = Str::random(12);
        } while (self::where('share_token', $token)->exists());
        return $token;
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }
}

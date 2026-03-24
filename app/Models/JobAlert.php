<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobAlert extends Model
{
    protected $fillable = ['user_id', 'keywords', 'location', 'employment_type', 'work_mode', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function matches(Job $job): bool
    {
        if ($this->keywords) {
            $kw = strtolower($this->keywords);
            $haystack = strtolower($job->title . ' ' . $job->description);
            if (!str_contains($haystack, $kw)) return false;
        }
        if ($this->location) {
            $loc = strtolower($this->location);
            $jobLoc = strtolower(($job->city ?? '') . ' ' . ($job->country ?? ''));
            if (!str_contains($jobLoc, $loc)) return false;
        }
        if ($this->employment_type && $this->employment_type !== $job->employment_type) {
            return false;
        }
        return true;
    }
}

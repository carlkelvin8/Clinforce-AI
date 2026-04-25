<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    protected $table = 'jobs_table';

    protected $fillable = [
        'owner_type',
        'owner_user_id',
        'title',
        'description',
        'employment_type',
        'work_mode',
        'country',
        'state',
        'city',
        'status',
        'published_at',
        'archived_at',
        'salary_min',
        'salary_max',
        'salary_type',
        'salary_currency',
        'closes_at',
        'view_count',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'archived_at'  => 'datetime',
        'closes_at'    => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'job_id');
    }

    public function aiScreenings(): HasMany
    {
        return $this->hasMany(AiScreening::class, 'job_id');
    }

    public function screeningQuestions(): HasMany
    {
        return $this->hasMany(ScreeningQuestion::class, 'job_id');
    }

    public function asyncInterviews(): HasMany
    {
        return $this->hasMany(AsyncInterview::class, 'job_id');
    }
}

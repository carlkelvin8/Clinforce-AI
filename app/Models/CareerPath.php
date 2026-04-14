<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CareerPath extends Model
{
    protected $fillable = [
        'name',
        'category',
        'from_role',
        'to_role',
        'description',
        'typical_duration_months',
        'avg_salary_increase',
        'required_steps',
        'required_certifications',
        'resources',
        'is_active',
    ];

    protected $casts = [
        'avg_salary_increase' => 'decimal:2',
        'required_steps' => 'array',
        'required_certifications' => 'array',
        'resources' => 'array',
        'is_active' => 'boolean',
    ];

    public function userGoals(): HasMany
    {
        return $this->hasMany(UserCareerGoal::class);
    }
}

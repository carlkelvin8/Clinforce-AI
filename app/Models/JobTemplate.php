<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobTemplate extends Model
{
    protected $fillable = [
        'owner_user_id',
        'name',
        'category',
        'role_type',
        'tags',
        'title',
        'description',
        'employment_type',
        'work_mode',
        'country',
        'city',
        'salary_min',
        'salary_max',
        'salary_currency',
        'is_ai_generated',
        'ai_model_used',
        'ai_suggestions',
        'required_certifications',
        'required_licenses',
        'shift_type',
        'shift_details',
        'experience_level',
        'min_experience_years',
        'benefits',
        'compliance_checklist',
        'ab_test_id',
        'ab_variant',
        'views_count',
        'conversions_count',
        'ab_test_started_at',
        'ab_test_ended_at',
        'is_ab_winner',
        'is_system_template',
        'usage_count',
        'avg_conversion_rate',
    ];

    protected $casts = [
        'salary_min' => 'float',
        'salary_max' => 'float',
        'tags' => 'array',
        'ai_suggestions' => 'array',
        'required_certifications' => 'array',
        'required_licenses' => 'array',
        'shift_details' => 'array',
        'benefits' => 'array',
        'compliance_checklist' => 'array',
        'ab_test_started_at' => 'datetime',
        'ab_test_ended_at' => 'datetime',
        'is_ai_generated' => 'boolean',
        'is_system_template' => 'boolean',
        'is_ab_winner' => 'boolean',
        'views_count' => 'integer',
        'conversions_count' => 'integer',
        'usage_count' => 'integer',
        'min_experience_years' => 'integer',
        'avg_conversion_rate' => 'decimal:2',
    ];

    protected $appends = ['conversion_rate'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function abTest(): BelongsTo
    {
        return $this->belongsTo(TemplateAbTest::class, 'ab_test_id');
    }

    public function getConversionRateAttribute(): ?float
    {
        if ($this->views_count > 0) {
            return round(($this->conversions_count / $this->views_count) * 100, 2);
        }
        return null;
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function incrementConversions(): void
    {
        $this->increment('conversions_count');
    }
}

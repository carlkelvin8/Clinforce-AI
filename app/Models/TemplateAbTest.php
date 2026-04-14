<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemplateAbTest extends Model
{
    protected $table = 'template_ab_tests';

    protected $fillable = [
        'owner_user_id',
        'name',
        'description',
        'base_template_id',
        'variant_ids',
        'test_type',
        'target_sample_size',
        'confidence_level',
        'started_at',
        'completed_at',
        'status',
        'results',
    ];

    protected $casts = [
        'variant_ids' => 'array',
        'results' => 'array',
        'confidence_level' => 'decimal:2',
        'target_sample_size' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function baseTemplate(): BelongsTo
    {
        return $this->belongsTo(JobTemplate::class, 'base_template_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(JobTemplate::class, 'ab_test_id');
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function calculateResults(): array
    {
        $variants = $this->variants()->get();
        $results = [];

        foreach ($variants as $variant) {
            $conversionRate = $variant->conversion_rate ?? 0;
            $results[$variant->ab_variant] = [
                'template_id' => $variant->id,
                'views' => $variant->views_count,
                'conversions' => $variant->conversions_count,
                'conversion_rate' => $conversionRate,
            ];
        }

        // Determine winner
        $winner = null;
        $highestRate = 0;

        foreach ($results as $variant => $data) {
            if ($data['conversion_rate'] > $highestRate) {
                $highestRate = $data['conversion_rate'];
                $winner = $variant;
            }
        }

        $results['winner'] = $winner;
        $results['total_views'] = array_sum(array_column($results, 'views'));
        $results['total_conversions'] = array_sum(array_column($results, 'conversions'));

        $this->update(['results' => $results]);

        return $results;
    }
}

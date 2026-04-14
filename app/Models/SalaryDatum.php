<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryDatum extends Model
{
    protected $table = 'salary_data';

    protected $fillable = [
        'role_type',
        'country',
        'state',
        'city',
        'experience_level',
        'min_years_experience',
        'salary_min',
        'salary_max',
        'salary_median',
        'salary_average',
        'currency',
        'salary_type',
        'data_date',
        'source',
    ];

    protected $casts = [
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
        'salary_median' => 'decimal:2',
        'salary_average' => 'decimal:2',
        'data_date' => 'datetime',
        'source' => 'array',
    ];

    public function getSalaryRangeAttribute(): string
    {
        $min = number_format($this->salary_min, 0);
        $max = number_format($this->salary_max, 0);
        return "{$min} - {$max} {$this->currency}/{$this->salary_type}";
    }
}

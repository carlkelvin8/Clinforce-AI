<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobTemplate extends Model
{
    protected $fillable = [
        'owner_user_id',
        'name',
        'title',
        'description',
        'employment_type',
        'work_mode',
        'country',
        'city',
        'salary_min',
        'salary_max',
        'salary_currency',
    ];

    protected $casts = [
        'salary_min' => 'float',
        'salary_max' => 'float',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}

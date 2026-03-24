<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicantProfile extends Model
{
    protected $table = 'applicant_profiles';
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'headline',
        'summary',
        'years_experience',
        'country',
        'state',
        'city',
        'public_display_name',
        'avatar',
        'skills',
        'work_experience',
        'education',
        'portfolio_links',
    ];

    protected $casts = [
        'years_experience' => 'integer',
        'skills' => 'array',
        'work_experience' => 'array',
        'education' => 'array',
        'portfolio_links' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    protected $fillable = [
        'employer_id',
        'candidate_id',
        'status',
        'message',
    ];

    public function employer()
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function candidate()
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    public function candidateProfile()
    {
        return $this->hasOne(ApplicantProfile::class, 'user_id', 'candidate_id');
    }
}

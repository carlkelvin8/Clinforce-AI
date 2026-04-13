<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScreeningAnswer extends Model
{
    protected $table = 'screening_answers';

    protected $fillable = [
        'question_id', 'application_id', 'answer', 'knockout_triggered',
    ];

    protected $casts = [
        'knockout_triggered' => 'boolean',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(ScreeningQuestion::class, 'question_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'application_id');
    }
}

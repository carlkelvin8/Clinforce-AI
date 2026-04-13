<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ScreeningQuestion extends Model
{
    protected $table = 'screening_questions';

    protected $fillable = [
        'job_id', 'question', 'type', 'options', 'is_knockout',
        'knockout_value', 'order', 'help_text', 'is_required',
    ];

    protected $casts = [
        'options' => 'array',
        'is_knockout' => 'boolean',
        'is_required' => 'boolean',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ScreeningAnswer::class, 'question_id');
    }

    /**
     * Check if the given answer value triggers knockout.
     */
    public function isKnockoutAnswer(string $answer): bool
    {
        if (!$this->is_knockout) {
            return false;
        }

        $value = strtolower(trim($this->knockout_value ?? ''));
        $answer = strtolower(trim($answer));

        // Direct match
        if ($answer === $value) {
            return true;
        }

        // Yes/No knockout: "no" is typically the knockout
        if ($this->type === 'yes_no' && $value === 'no' && $answer === 'no') {
            return true;
        }

        // Numeric comparison (e.g. knockout_value = "<2" means less than 2)
        if (preg_match('/^([<>=!]+)\s*(\d+(?:\.\d+)?)$/', $value, $matches)) {
            $operator = $matches[1];
            $threshold = (float) $matches[2];
            $numAnswer = (float) $answer;

            return match ($operator) {
                '<'   => $numAnswer < $threshold,
                '<='  => $numAnswer <= $threshold,
                '>'   => $numAnswer > $threshold,
                '>='  => $numAnswer >= $threshold,
                '=='  => $numAnswer == $threshold,
                '!='  => $numAnswer != $threshold,
                default => false,
            };
        }

        return false;
    }
}

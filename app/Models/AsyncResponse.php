<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsyncResponse extends Model
{
    protected $table = 'async_responses';

    protected $fillable = [
        'async_interview_id', 'application_id', 'user_id', 'answers',
        'status', 'started_at', 'completed_at', 'user_agent', 'ip_address',
    ];

    protected $casts = [
        'answers' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function asyncInterview(): BelongsTo
    {
        return $this->belongsTo(AsyncInterview::class, 'async_interview_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'application_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Total duration of all recorded answers in seconds.
     */
    public function totalDurationSeconds(): int
    {
        $total = 0;
        foreach ($this->answers ?? [] as $answer) {
            $total += (int) ($answer['duration_sec'] ?? 0);
        }
        return $total;
    }

    /**
     * Number of questions answered.
     */
    public function answeredCount(): int
    {
        return count($this->answers ?? []);
    }

    /**
     * Check if response is complete (all questions answered).
     */
    public function isComplete(): bool
    {
        $totalQuestions = count($this->asyncInterview->questions ?? []);
        return $this->answeredCount() >= $totalQuestions && $totalQuestions > 0;
    }
}

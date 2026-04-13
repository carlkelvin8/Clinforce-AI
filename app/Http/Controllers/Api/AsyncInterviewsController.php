<?php

namespace App\Http\Controllers\Api;

use App\Models\AsyncInterview;
use App\Models\AsyncResponse;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AsyncInterviewsController extends ApiController
{
    /**
     * GET /jobs/{job}/async-interviews
     */
    public function index(Job $job): JsonResponse
    {
        $u = $this->requireAuth();

        $isOwner = in_array($u->role, ['employer', 'agency'], true)
            && $job->owner_user_id === $u->id
            && $job->owner_type === $u->role;

        if ($u->role !== 'admin' && !$isOwner) {
            return $this->fail('Forbidden', null, 403);
        }

        $interviews = $job->asyncInterviews()
            ->withCount('responses')
            ->orderByDesc('created_at')
            ->get();

        return $this->ok($interviews);
    }

    /**
     * POST /jobs/{job}/async-interviews
     * Create an async interview session with questions.
     */
    public function store(Request $request, Job $job): JsonResponse
    {
        $u = $this->requireAuth();

        $isOwner = in_array($u->role, ['employer', 'agency'], true)
            && $job->owner_user_id === $u->id
            && $job->owner_type === $u->role;

        if ($u->role !== 'admin' && !$isOwner) {
            return $this->fail('Forbidden', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'questions' => 'required|array|min:1|max:20',
            'questions.*.question' => 'required|string|max:1000',
            'questions.*.time_limit_sec' => 'nullable|integer|min:15|max:600',
            'questions.*.max_duration_sec' => 'nullable|integer|min:30|max:1800',
            'questions.*.allow_retries' => 'nullable|boolean',
            'max_duration_minutes' => 'nullable|integer|min:5|max:120',
            'allow_retries' => 'nullable|boolean',
            'expires_at' => 'nullable|date|after:now',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed', $validator->errors(), 422);
        }

        // Generate IDs for questions
        $questions = collect($request->questions)->map(function ($q, $i) {
            return [
                'id' => Str::uuid()->toString(),
                'question' => $q['question'],
                'time_limit_sec' => $q['time_limit_sec'] ?? 120,
                'max_duration_sec' => $q['max_duration_sec'] ?? 300,
                'allow_retries' => $q['allow_retries'] ?? ($request->allow_retries ?? false),
            ];
        })->all();

        $interview = AsyncInterview::create([
            'job_id' => $job->id,
            'title' => $request->title,
            'description' => $request->description,
            'questions' => $questions,
            'max_duration_minutes' => $request->max_duration_minutes ?? 15,
            'allow_retries' => $request->boolean('allow_retries', false),
            'expires_at' => $request->expires_at ? $request->expires_at : null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->ok($interview, 'Async interview created', 201);
    }

    /**
     * GET /async-interviews/{asyncInterview}
     */
    public function show(AsyncInterview $asyncInterview): JsonResponse
    {
        $u = $this->requireAuth();

        $asyncInterview->load(['job', 'responses.user']);

        $job = $asyncInterview->job;
        $isOwner = in_array($u->role, ['employer', 'agency'], true)
            && $job->owner_user_id === $u->id
            && $job->owner_type === $u->role;

        if ($u->role !== 'admin' && !$isOwner) {
            return $this->fail('Forbidden', null, 403);
        }

        return $this->ok($asyncInterview);
    }

    /**
     * PUT /async-interviews/{asyncInterview}
     */
    public function update(Request $request, AsyncInterview $asyncInterview): JsonResponse
    {
        $u = $this->requireAuth();

        $job = $asyncInterview->job;
        $isOwner = in_array($u->role, ['employer', 'agency'], true)
            && $job->owner_user_id === $u->id
            && $job->owner_type === $u->role;

        if ($u->role !== 'admin' && !$isOwner) {
            return $this->fail('Forbidden', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:2000',
            'questions' => 'sometimes|array|min:1|max:20',
            'questions.*.question' => 'required|string|max:1000',
            'questions.*.time_limit_sec' => 'nullable|integer|min:15|max:600',
            'questions.*.max_duration_sec' => 'nullable|integer|min:30|max:1800',
            'max_duration_minutes' => 'nullable|integer|min:5|max:120',
            'allow_retries' => 'nullable|boolean',
            'expires_at' => 'nullable|date|after:now',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed', $validator->errors(), 422);
        }

        $update = $validator->validated();

        if (isset($update['questions'])) {
            // Preserve existing IDs for unchanged questions, generate new for others
            $existing = collect($asyncInterview->questions ?? []);
            $new = collect($update['questions'])->map(function ($q, $i) use ($existing) {
                $match = $existing->first(function ($eq) use ($q) {
                    return ($eq['question'] ?? '') === $q['question'];
                });

                return [
                    'id' => $match['id'] ?? Str::uuid()->toString(),
                    'question' => $q['question'],
                    'time_limit_sec' => $q['time_limit_sec'] ?? ($match['time_limit_sec'] ?? 120),
                    'max_duration_sec' => $q['max_duration_sec'] ?? ($match['max_duration_sec'] ?? 300),
                    'allow_retries' => $q['allow_retries'] ?? ($match['allow_retries'] ?? false),
                ];
            })->all();
            $update['questions'] = $new;
        }

        $asyncInterview->update($update);

        return $this->ok($asyncInterview, 'Async interview updated');
    }

    /**
     * DELETE /async-interviews/{asyncInterview}
     */
    public function destroy(AsyncInterview $asyncInterview): JsonResponse
    {
        $u = $this->requireAuth();

        $job = $asyncInterview->job;
        $isOwner = in_array($u->role, ['employer', 'agency'], true)
            && $job->owner_user_id === $u->id
            && $job->owner_type === $u->role;

        if ($u->role !== 'admin' && !$isOwner) {
            return $this->fail('Forbidden', null, 403);
        }

        $asyncInterview->delete();

        return $this->ok(null, 'Async interview deleted');
    }

    // ── Candidate-facing endpoints ──

    /**
     * GET /async-interviews/{asyncInterview}/session
     * Get questions for a candidate to answer (without revealing answers).
     */
    public function getSession(AsyncInterview $asyncInterview): JsonResponse
    {
        $u = $this->requireAuth();

        if (!$asyncInterview->is_active) {
            return $this->fail('This interview session is no longer active', null, 410);
        }

        if ($asyncInterview->expires_at && now()->gt($asyncInterview->expires_at)) {
            return $this->fail('This interview session has expired', null, 410);
        }

        // Check if candidate already has a response
        $application = $u->role === 'applicant'
            ? \App\Models\JobApplication::where('job_id', $asyncInterview->job_id)
                ->where('applicant_user_id', $u->id)
                ->first()
            : null;

        $existingResponse = $application
            ? AsyncResponse::where('async_interview_id', $asyncInterview->id)
                ->where('application_id', $application->id)
                ->first()
            : null;

        // Return questions only (no answers from other candidates)
        $questions = collect($asyncInterview->questions ?? [])->map(function ($q) {
            return [
                'id' => $q['id'],
                'question' => $q['question'],
                'time_limit_sec' => $q['time_limit_sec'] ?? 120,
                'max_duration_sec' => $q['max_duration_sec'] ?? 300,
                'allow_retries' => $q['allow_retries'] ?? false,
            ];
        });

        return $this->ok([
            'interview' => [
                'id' => $asyncInterview->id,
                'title' => $asyncInterview->title,
                'description' => $asyncInterview->description,
                'max_duration_minutes' => $asyncInterview->max_duration_minutes,
                'allow_retries' => $asyncInterview->allow_retries,
                'expires_at' => $asyncInterview->expires_at,
            ],
            'questions' => $questions,
            'existing_response' => $existingResponse ? [
                'status' => $existingResponse->status,
                'completed_at' => $existingResponse->completed_at,
                'answered_count' => $existingResponse->answeredCount(),
            ] : null,
        ]);
    }

    /**
     * POST /async-interviews/{asyncInterview}/start
     * Start recording answers (creates a response session).
     */
    public function startSession(Request $request, AsyncInterview $asyncInterview): JsonResponse
    {
        $u = $this->requireAuth();

        if ($u->role !== 'applicant') {
            return $this->fail('Only candidates can start async interviews', null, 403);
        }

        if (!$asyncInterview->isAcceptingResponses()) {
            return $this->fail('This interview is no longer accepting responses', null, 410);
        }

        $application = \App\Models\JobApplication::where('job_id', $asyncInterview->job_id)
            ->where('applicant_user_id', $u->id)
            ->first();

        if (!$application) {
            return $this->fail('You must apply for this job first', null, 403);
        }

        $existing = AsyncResponse::where('async_interview_id', $asyncInterview->id)
            ->where('application_id', $application->id)
            ->first();

        if ($existing && $existing->status === 'completed') {
            return $this->fail('You have already completed this interview', null, 409);
        }

        if ($existing && $existing->status === 'in_progress') {
            return $this->ok($existing, 'Resume your existing session');
        }

        $response = AsyncResponse::create([
            'async_interview_id' => $asyncInterview->id,
            'application_id' => $application->id,
            'user_id' => $u->id,
            'answers' => [],
            'status' => 'in_progress',
            'started_at' => now(),
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
        ]);

        // Increment response counter
        $asyncInterview->increment('total_responses');

        return $this->ok($response, 'Session started', 201);
    }

    /**
     * POST /async-interviews/{asyncInterview}/upload
     * Upload a video answer for a specific question.
     */
    public function uploadAnswer(Request $request, AsyncInterview $asyncInterview): JsonResponse
    {
        $u = $this->requireAuth();

        if ($u->role !== 'applicant') {
            return $this->fail('Only candidates can upload answers', null, 403);
        }

        $application = \App\Models\JobApplication::where('job_id', $asyncInterview->job_id)
            ->where('applicant_user_id', $u->id)
            ->first();

        if (!$application) {
            return $this->fail('Application not found', null, 404);
        }

        $response = AsyncResponse::where('async_interview_id', $asyncInterview->id)
            ->where('application_id', $application->id)
            ->first();

        if (!$response) {
            return $this->fail('Start a session first', null, 400);
        }

        if ($response->status === 'completed') {
            return $this->fail('Interview already completed', null, 409);
        }

        $validator = Validator::make($request->all(), [
            'question_id' => 'required|string',
            'video_url' => 'required|string|max:2000',
            'thumbnail_url' => 'nullable|string|max:2000',
            'duration_sec' => 'required|integer|min:1',
            'transcript' => 'nullable|string|max:10000',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed', $validator->errors(), 422);
        }

        // Validate question exists and is in the interview
        $question = collect($asyncInterview->questions ?? [])
            ->firstWhere('id', $request->question_id);

        if (!$question) {
            return $this->fail('Question not found in this interview', null, 404);
        }

        $validated = $validator->validated();

        $answers = $response->answers ?? [];
        $retryCount = 0;

        // Check for existing answer to this question
        $existingIndex = null;
        foreach ($answers as $i => $a) {
            if ($a['question_id'] === $request->question_id) {
                $existingIndex = $i;
                $retryCount = ($a['retry_count'] ?? 0) + 1;
                break;
            }
        }

        // Check retry limit
        $maxRetries = $question['allow_retries'] ?? $asyncInterview->allow_retries ? 3 : 0;
        if ($retryCount > $maxRetries && $maxRetries > 0) {
            return $this->fail("Maximum retries ({$maxRetries}) exceeded for this question", null, 429);
        }

        $answerData = [
            'question_id' => $request->question_id,
            'video_url' => $validated['video_url'],
            'thumbnail_url' => $validated['thumbnail_url'] ?? null,
            'duration_sec' => $validated['duration_sec'],
            'transcript' => $validated['transcript'] ?? null,
            'retry_count' => $retryCount,
            'created_at' => now()->toIso8601String(),
        ];

        if ($existingIndex !== null) {
            $answers[$existingIndex] = $answerData;
        } else {
            $answers[] = $answerData;
        }

        $response->update([
            'answers' => $answers,
            'status' => count($answers) >= count($asyncInterview->questions ?? [])
                ? 'completed'
                : 'in_progress',
            'completed_at' => count($answers) >= count($asyncInterview->questions ?? [])
                ? now()
                : null,
        ]);

        return $this->ok($response, 'Answer uploaded');
    }

    /**
     * GET /async-interviews/{asyncInterview}/responses
     * Employer: view all candidate responses.
     */
    public function responses(AsyncInterview $asyncInterview): JsonResponse
    {
        $u = $this->requireAuth();

        $job = $asyncInterview->job;
        $isOwner = in_array($u->role, ['employer', 'agency'], true)
            && $job->owner_user_id === $u->id
            && $job->owner_type === $u->role;

        if ($u->role !== 'admin' && !$isOwner) {
            return $this->fail('Forbidden', null, 403);
        }

        $responses = $asyncInterview->responses()
            ->with(['application.applicant', 'application.applicant.applicantProfile'])
            ->orderByDesc('created_at')
            ->get();

        return $this->ok($responses);
    }

    /**
     * GET /async-interviews/{asyncInterview}/responses/{asyncResponse}
     * View a specific candidate's response (employer).
     */
    public function showResponse(AsyncInterview $asyncInterview, AsyncResponse $asyncResponse): JsonResponse
    {
        $u = $this->requireAuth();

        $job = $asyncInterview->job;
        $isOwner = in_array($u->role, ['employer', 'agency'], true)
            && $job->owner_user_id === $u->id
            && $job->owner_type === $u->role;

        if ($u->role !== 'admin' && !$isOwner) {
            return $this->fail('Forbidden', null, 403);
        }

        if ($asyncResponse->async_interview_id !== $asyncInterview->id) {
            return $this->fail('Response not found for this interview', null, 404);
        }

        $asyncResponse->load(['application.applicant', 'application.applicant.applicantProfile']);

        return $this->ok($asyncResponse);
    }
}

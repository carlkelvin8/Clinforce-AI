<?php
// app/Http/Controllers/Api/AiScreeningsController.php

namespace App\Http\Controllers\Api;

use App\Models\AiScreening;
use App\Models\JobApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class AiScreeningsController extends ApiController
{
    // GET /ai-screenings?application_id=...
    // Admin OR job owner OR applicant (their own application)
    public function index(): JsonResponse
    {
        $u = $this->requireAuth();

        $applicationId = request()->query('application_id');
        if (!$applicationId) {
            return $this->fail('application_id is required', ['application_id' => ['Required']], 422);
        }

        $application = JobApplication::query()
            ->with('job')
            ->find($applicationId);

        if (!$application) return $this->fail('Not found', null, 404);

        $isApplicant = $application->applicant_user_id === $u->id;
        $isOwner = $application->job
            && in_array($u->role, ['employer','agency'], true)
            && $application->job->owner_user_id === $u->id
            && $application->job->owner_type === $u->role;

        if ($u->role !== 'admin' && !$isApplicant && !$isOwner) {
            return $this->fail('Forbidden', null, 403);
        }

        $rows = AiScreening::query()
            ->where('application_id', $application->id)
            ->orderByDesc('id')
            ->get();

        return $this->ok($rows);
    }

    // POST /applications/{application}/ai-screening
    // Requires subscription:ai middleware (set in routes)
    public function store(JobApplication $application): JsonResponse
    {
        $u = $this->requireAuth();

        $application->load(['job', 'applicant.applicantProfile']);

        $isOwner = $application->job
            && in_array($u->role, ['employer', 'agency'], true)
            && $application->job->owner_user_id === $u->id
            && $application->job->owner_type === $u->role;

        if ($u->role !== 'admin' && !$isOwner) {
            return $this->fail('Only the job owner can trigger AI screening', null, 403);
        }

        // Prevent duplicate screenings
        $existing = AiScreening::query()
            ->where('application_id', $application->id)
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            return $this->ok($existing, 'Screening already exists');
        }

        $job = $application->job;
        $profile = $application->applicant?->applicantProfile;

        $jobDescription = trim(strip_tags($job->description ?? ''));
        $applicantSummary = implode("\n", array_filter([
            $profile ? "Name: {$profile->first_name} {$profile->last_name}" : null,
            $profile?->headline ? "Headline: {$profile->headline}" : null,
            $profile?->summary ? "Summary: {$profile->summary}" : null,
            $profile?->years_experience !== null ? "Years of experience: {$profile->years_experience}" : null,
            $application->cover_letter ? "Cover letter: {$application->cover_letter}" : null,
        ]));

        $prompt = <<<PROMPT
You are an expert healthcare recruiter AI. Evaluate the following job application.

JOB TITLE: {$job->title}
JOB DESCRIPTION:
{$jobDescription}

APPLICANT PROFILE:
{$applicantSummary}

Respond ONLY with a valid JSON object (no markdown, no explanation) with these exact keys:
- "score": integer 0-100 representing overall fit
- "summary": 2-3 sentence plain-text summary of the applicant's fit
- "suggestions": array of 2-4 short strings with actionable suggestions for the employer
PROMPT;

        $apiKey = config('services.openai.api_key') ?: env('OPENAI_API_KEY');
        if (!$apiKey) {
            return $this->fail('OpenAI API key not configured', null, 500);
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a healthcare recruitment AI. Always respond with valid JSON only.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 500,
                ]);

            if (!$response->successful()) {
                \Log::error('OpenAI API error', ['status' => $response->status(), 'body' => $response->body()]);
                return $this->fail('AI screening failed: ' . $response->status(), null, 502);
            }

            $content = $response->json('choices.0.message.content', '');
            $parsed = json_decode($content, true);

            if (!$parsed || !isset($parsed['score'], $parsed['summary'])) {
                \Log::error('OpenAI invalid JSON response', ['content' => $content]);
                return $this->fail('AI returned an invalid response. Please try again.', null, 502);
            }

            $screening = AiScreening::create([
                'application_id' => $application->id,
                'job_id' => $job->id,
                'applicant_user_id' => $application->applicant_user_id,
                'model_name' => 'gpt-4o-mini',
                'score' => min(100, max(0, (int) $parsed['score'])),
                'summary' => (string) $parsed['summary'],
                'suggestions' => (array) ($parsed['suggestions'] ?? []),
                'created_at' => now(),
            ]);

            return $this->ok($screening, 'AI screening complete', 201);

        } catch (\Exception $e) {
            \Log::error('AI screening exception', ['error' => $e->getMessage()]);
            return $this->fail('AI screening failed: ' . $e->getMessage(), null, 500);
        }
    }
}

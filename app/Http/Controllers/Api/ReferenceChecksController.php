<?php

namespace App\Http\Controllers\Api;

use App\Mail\ReferenceReceived;
use App\Mail\ReferenceRequest;
use App\Models\JobApplication;
use App\Models\ReferenceCheck;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ReferenceChecksController extends ApiController
{
    /**
     * GET /applications/{application}/reference-checks
     */
    public function index(JobApplication $application): JsonResponse
    {
        $u = $this->requireAuth();

        $application->load('job');
        $isOwner = in_array($u->role, ['employer', 'agency'], true)
            && $application->job?->owner_user_id === $u->id
            && $application->job?->owner_type === $u->role;

        if ($u->role !== 'admin' && !$isOwner) {
            return $this->fail('Forbidden', null, 403);
        }

        $checks = $application->referenceChecks()
            ->with('requestedBy')
            ->orderByDesc('created_at')
            ->get();

        return $this->ok($checks);
    }

    /**
     * POST /applications/{application}/reference-checks
     * Send a reference request to a referee.
     */
    public function store(Request $request, JobApplication $application): JsonResponse
    {
        $u = $this->requireAuth();

        $application->load('job');
        $isOwner = in_array($u->role, ['employer', 'agency'], true)
            && $application->job?->owner_user_id === $u->id
            && $application->job?->owner_type === $u->role;

        if ($u->role !== 'admin' && !$isOwner) {
            return $this->fail('Forbidden', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'referee_name' => 'required|string|max:255',
            'referee_email' => 'required|email|max:255',
            'referee_phone' => 'nullable|string|max:50',
            'referee_relationship' => 'nullable|string|max:255',
            'referee_title' => 'nullable|string|max:255',
            'referee_company' => 'nullable|string|max:255',
            'questions' => 'nullable|array',
            'expires_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed', $validator->errors(), 422);
        }

        $validated = $validator->validated();

        // Check for duplicate (same email + same application, pending/sent)
        $duplicate = ReferenceCheck::where('application_id', $application->id)
            ->where('referee_email', strtolower($validated['referee_email']))
            ->whereIn('status', ['pending', 'sent'])
            ->first();

        if ($duplicate) {
            return $this->fail('A reference request for this referee is already pending', null, 409);
        }

        $check = ReferenceCheck::create([
            'application_id' => $application->id,
            'requested_by_user_id' => $u->id,
            'referee_name' => $validated['referee_name'],
            'referee_email' => strtolower($validated['referee_email']),
            'referee_phone' => $validated['referee_phone'] ?? null,
            'referee_relationship' => $validated['referee_relationship'] ?? null,
            'referee_title' => $validated['referee_title'] ?? null,
            'referee_company' => $validated['referee_company'] ?? null,
            'questions' => $validated['questions'] ?? ReferenceCheck::defaultQuestions(),
            'status' => 'pending',
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        // Send email to referee
        try {
            $this->sendReferenceRequestEmail($check, $application);
            $check->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (\Throwable $e) {
            \Log::warning('Failed to send reference request email', [
                'check_id' => $check->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->ok($check, 'Reference request sent', 201);
    }

    /**
     * GET /reference-checks/{referenceCheck}
     * Show details (employer side).
     */
    public function show(ReferenceCheck $referenceCheck): JsonResponse
    {
        $u = $this->requireAuth();

        $referenceCheck->load(['application.job', 'requestedBy']);

        $isOwner = in_array($u->role, ['employer', 'agency'], true)
            && $referenceCheck->application?->job?->owner_user_id === $u->id
            && $referenceCheck->application?->job?->owner_type === $u->role;

        if ($u->role !== 'admin' && !$isOwner) {
            return $this->fail('Forbidden', null, 403);
        }

        return $this->ok($referenceCheck);
    }

    /**
     * POST /reference-checks/{referenceCheck}/remind
     * Send a reminder to the referee.
     */
    public function remind(ReferenceCheck $referenceCheck): JsonResponse
    {
        $u = $this->requireAuth();

        $referenceCheck->load('application.job');
        $isOwner = in_array($u->role, ['employer', 'agency'], true)
            && $referenceCheck->application?->job?->owner_user_id === $u->id
            && $referenceCheck->application?->job?->owner_type === $u->role;

        if ($u->role !== 'admin' && !$isOwner) {
            return $this->fail('Forbidden', null, 403);
        }

        if ($referenceCheck->status !== 'sent') {
            return $this->fail('Can only remind pending/sent requests', null, 400);
        }

        // Rate limit reminders (max 1 per 48h)
        if ($referenceCheck->last_reminder_at && $referenceCheck->last_reminder_at->gt(now()->subHours(48))) {
            return $this->fail('Reminder already sent recently. Please wait 48 hours.', null, 429);
        }

        try {
            $this->sendReferenceRequestEmail($referenceCheck, $referenceCheck->application);
            $referenceCheck->increment('reminder_count');
            $referenceCheck->update(['last_reminder_at' => now()]);
        } catch (\Throwable $e) {
            return $this->fail('Failed to send reminder: ' . $e->getMessage(), null, 500);
        }

        return $this->ok($referenceCheck, 'Reminder sent');
    }

    /**
     * DELETE /reference-checks/{referenceCheck}
     */
    public function destroy(ReferenceCheck $referenceCheck): JsonResponse
    {
        $u = $this->requireAuth();

        $referenceCheck->load('application.job');
        $isOwner = in_array($u->role, ['employer', 'agency'], true)
            && $referenceCheck->application?->job?->owner_user_id === $u->id
            && $referenceCheck->application?->job?->owner_type === $u->role;

        if ($u->role !== 'admin' && !$isOwner) {
            return $this->fail('Forbidden', null, 403);
        }

        if ($referenceCheck->status === 'completed') {
            return $this->fail('Cannot delete completed reference checks', null, 409);
        }

        $referenceCheck->update(['status' => 'cancelled']);
        $referenceCheck->delete();

        return $this->ok(null, 'Reference check cancelled');
    }

    // ── Referee-facing endpoints ──

    /**
     * GET /reference-checks/respond/{token}
     * Get reference check questions (referee public link).
     */
    public function respondByToken(string $token): JsonResponse
    {
        $check = ReferenceCheck::where('token', $token)->first();

        if (!$check) {
            return $this->fail('Invalid reference link', null, 404);
        }

        if ($check->status === 'completed') {
            return $this->ok(null, 'Thank you! Your reference has already been submitted.');
        }

        if ($check->expires_at && now()->gt($check->expires_at)) {
            return $this->fail('This reference request has expired', null, 410);
        }

        $candidateName = $check->application?->applicant?->name ?? 'the candidate';
        $jobTitle = $check->application?->job?->title ?? 'a position';

        return $this->ok([
            'referee_name' => $check->referee_name,
            'candidate_name' => $candidateName,
            'job_title' => $jobTitle,
            'questions' => $check->questions ?? ReferenceCheck::defaultQuestions(),
            'expires_at' => $check->expires_at,
        ]);
    }

    /**
     * POST /reference-checks/respond/{token}
     * Submit reference responses (referee).
     */
    public function submitResponse(Request $request, string $token): JsonResponse
    {
        $check = ReferenceCheck::where('token', $token)->first();

        if (!$check) {
            return $this->fail('Invalid reference link', null, 404);
        }

        if ($check->status === 'completed') {
            return $this->fail('Reference already submitted', null, 409);
        }

        if ($check->expires_at && now()->gt($check->expires_at)) {
            return $this->fail('This reference request has expired', null, 410);
        }

        $validator = Validator::make($request->all(), [
            'responses' => 'nullable|array',
            'responses.*.question' => 'required|string',
            'responses.*.answer' => 'nullable|string|max:5000',
            'responses.*.rating' => 'nullable|integer|min:1|max:5',
            'comments' => 'nullable|string|max:5000',
            'rating' => 'nullable|numeric|min:1|max:5',
            'would_rehire' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->fail('Validation failed', $validator->errors(), 422);
        }

        $check->update([
            'responses' => $request->responses ?? [],
            'comments' => $request->comments ?? null,
            'rating' => $request->rating ?? null,
            'would_rehire' => $request->boolean('would_rehire') ? true : ($request->has('would_rehire') ? false : null),
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Notify employer
        try {
            $this->sendReferenceReceivedEmail($check);
        } catch (\Throwable $e) {
            \Log::warning('Failed to send reference received email', ['error' => $e->getMessage()]);
        }

        return $this->ok(null, 'Thank you! Your reference has been submitted successfully.');
    }

    // ── Private helpers ──

    private function sendReferenceRequestEmail(ReferenceCheck $check, ?JobApplication $application): void
    {
        $candidateName = $application?->applicant?->name ?? 'a candidate';
        $jobTitle = $application?->job?->title ?? 'a position';
        $respondUrl = config('app.url') . '/reference/respond/' . $check->token;

        Mail::to($check->referee_email)
            ->send(new ReferenceRequest($check, $candidateName, $jobTitle, $respondUrl));
    }

    private function sendReferenceReceivedEmail(ReferenceCheck $check): void
    {
        $employerEmail = $check->application?->job?->owner?->email;
        if (!$employerEmail) {
            return;
        }

        $candidateName = $check->application?->applicant?->name ?? 'the candidate';

        Mail::to($employerEmail)
            ->send(new ReferenceReceived($check, $candidateName));
    }
}

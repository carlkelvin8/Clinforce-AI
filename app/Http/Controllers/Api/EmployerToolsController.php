<?php

namespace App\Http\Controllers\Api;

use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class EmployerToolsController extends ApiController
{
    private function requireEmployer(): \App\Models\User
    {
        $u = $this->requireAuth();
        if (!in_array($u->role, ['employer', 'agency', 'admin'], true)) {
            abort(403, 'Employers only');
        }
        return $u;
    }

    // ── Offer Letter ─────────────────────────────────────────────────────
    public function offerLetter(Request $request, JobApplication $application): \Illuminate\Http\Response
    {
        $u = $this->requireEmployer();

        $application->load(['job', 'applicant.applicantProfile']);

        $isOwner = $application->job
            && $application->job->owner_user_id === $u->id
            && $application->job->owner_type === $u->role;

        if ($u->role !== 'admin' && !$isOwner) abort(403);
        if ($application->status !== 'hired') abort(422, 'Candidate must be hired first');

        $data = $request->validate([
            'start_date'  => 'nullable|date',
            'salary'      => 'nullable|string|max:100',
            'notes'       => 'nullable|string|max:1000',
        ]);

        $profile = $application->applicant?->applicantProfile;
        $firstName = $profile?->first_name ?? 'Candidate';
        $lastName  = $profile?->last_name  ?? '';
        $jobTitle  = $application->job?->title ?? 'Position';
        $startDate = isset($data['start_date']) ? date('F j, Y', strtotime($data['start_date'])) : 'To be confirmed';
        $salary    = $data['salary'] ?? 'As discussed';
        $notes     = $data['notes'] ?? '';
        $today     = date('F j, Y');

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  body { font-family: Georgia, serif; color: #1a1a1a; max-width: 700px; margin: 40px auto; padding: 40px; line-height: 1.7; }
  h1 { color: #1e3a5f; font-size: 22px; border-bottom: 2px solid #1e3a5f; padding-bottom: 8px; }
  .label { font-weight: bold; color: #555; }
  .section { margin: 24px 0; }
  .footer { margin-top: 60px; border-top: 1px solid #ccc; padding-top: 20px; font-size: 13px; color: #888; }
</style>
</head>
<body>
<p>{$today}</p>
<h1>Offer of Employment</h1>
<p>Dear {$firstName} {$lastName},</p>
<p>We are pleased to offer you the position of <strong>{$jobTitle}</strong>.</p>
<div class="section">
  <p><span class="label">Position:</span> {$jobTitle}</p>
  <p><span class="label">Start Date:</span> {$startDate}</p>
  <p><span class="label">Compensation:</span> {$salary}</p>
</div>
HTML;

        if ($notes) {
            $html .= "<div class=\"section\"><p><span class=\"label\">Additional Terms:</span></p><p>" . nl2br(htmlspecialchars($notes)) . "</p></div>";
        }

        $html .= <<<HTML
<p>Please sign and return this letter to confirm your acceptance.</p>
<div class="section">
  <p>Accepted by: _____________________________ Date: ___________</p>
</div>
<div class="footer">
  <p>ClinForce AI — Healthcare Recruitment Platform</p>
</div>
</body>
</html>
HTML;

        return response($html, 200, [
            'Content-Type'        => 'text/html; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="offer-letter-' . $application->id . '.html"',
        ]);
    }

    // ── Blacklist ─────────────────────────────────────────────────────────
    public function blacklistIndex(): JsonResponse
    {
        $u = $this->requireEmployer();
        $list = DB::table('employer_blacklist')
            ->where('employer_user_id', $u->id)
            ->join('users', 'users.id', '=', 'employer_blacklist.candidate_user_id')
            ->select('employer_blacklist.*', 'users.email')
            ->orderByDesc('employer_blacklist.id')
            ->get();
        return $this->ok($list);
    }

    public function blacklistAdd(Request $request): JsonResponse
    {
        $u = $this->requireEmployer();
        $data = $request->validate([
            'candidate_user_id' => 'required|integer|exists:users,id',
            'reason'            => 'nullable|string|max:500',
        ]);

        DB::table('employer_blacklist')->updateOrInsert(
            ['employer_user_id' => $u->id, 'candidate_user_id' => $data['candidate_user_id']],
            ['reason' => $data['reason'] ?? null, 'created_at' => now(), 'updated_at' => now()]
        );

        return $this->ok(['message' => 'Added to blacklist']);
    }

    public function blacklistRemove(int $candidateId): JsonResponse
    {
        $u = $this->requireEmployer();
        DB::table('employer_blacklist')
            ->where('employer_user_id', $u->id)
            ->where('candidate_user_id', $candidateId)
            ->delete();
        return $this->ok(['message' => 'Removed from blacklist']);
    }

    public function isBlacklisted(int $candidateId): JsonResponse
    {
        $u = $this->requireEmployer();
        $exists = DB::table('employer_blacklist')
            ->where('employer_user_id', $u->id)
            ->where('candidate_user_id', $candidateId)
            ->exists();
        return $this->ok(['blacklisted' => $exists]);
    }

    // ── Job Analytics ─────────────────────────────────────────────────────
    public function jobAnalytics(Job $job): JsonResponse
    {
        $u = $this->requireEmployer();

        $isOwner = $job->owner_user_id === $u->id && $job->owner_type === $u->role;
        if ($u->role !== 'admin' && !$isOwner) abort(403);

        $apps = JobApplication::query()
            ->where('job_id', $job->id)
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('DATE(created_at) as day'))
            ->groupBy('status', 'day')
            ->orderBy('day')
            ->get();

        $byStatus = JobApplication::query()
            ->where('job_id', $job->id)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $total = $byStatus->sum();
        $hired = $byStatus->get('hired', 0);

        // Daily trend
        $trend = JobApplication::query()
            ->where('job_id', $job->id)
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as count'))
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return $this->ok([
            'job_id'          => $job->id,
            'title'           => $job->title,
            'view_count'      => $job->view_count ?? 0,
            'total_apps'      => $total,
            'conversion_rate' => $total > 0 ? round(($hired / $total) * 100, 1) : 0,
            'by_status'       => $byStatus,
            'trend'           => $trend,
        ]);
    }

    // ── Bulk Message to Shortlisted ───────────────────────────────────────
    public function bulkMessage(Request $request, Job $job): JsonResponse
    {
        $u = $this->requireEmployer();

        $isOwner = $job->owner_user_id === $u->id && $job->owner_type === $u->role;
        if ($u->role !== 'admin' && !$isOwner) abort(403);

        $data = $request->validate([
            'message' => 'required|string|max:2000',
            'status'  => 'nullable|in:shortlisted,interview,submitted',
        ]);

        $targetStatus = $data['status'] ?? 'shortlisted';

        $applications = JobApplication::query()
            ->where('job_id', $job->id)
            ->where('status', $targetStatus)
            ->with('applicant:id,email,role')
            ->get();

        if ($applications->isEmpty()) {
            return $this->fail("No {$targetStatus} candidates found for this job", null, 422);
        }

        $sent = 0;
        $subject = "Update regarding your application for {$job->title}";

        foreach ($applications as $app) {
            $candidateId = $app->applicant_user_id;
            if (!$candidateId) continue;

            try {
                DB::transaction(function () use ($u, $candidateId, $data, $subject) {
                    $conv = \App\Models\Conversation::query()->create([
                        'created_by_user_id' => $u->id,
                        'subject' => $subject,
                    ]);

                    foreach ([$u->id, $candidateId] as $pid) {
                        \App\Models\ConversationParticipant::query()->create([
                            'conversation_id' => $conv->id,
                            'user_id'         => $pid,
                            'role_at_join'    => $pid === $u->id ? $u->role : 'applicant',
                            'last_read_at'    => $pid === $u->id ? now() : null,
                            'created_at'      => now(),
                        ]);
                    }

                    \App\Models\Message::query()->create([
                        'conversation_id' => $conv->id,
                        'sender_user_id'  => $u->id,
                        'body'            => $data['message'],
                        'created_at'      => now(),
                    ]);
                });
                $sent++;
            } catch (\Throwable $e) {
                \Log::warning("Bulk message failed for candidate {$candidateId}: " . $e->getMessage());
            }
        }

        return $this->ok(['sent' => $sent, 'total' => $applications->count()], "Message sent to {$sent} candidate(s)");
    }
}

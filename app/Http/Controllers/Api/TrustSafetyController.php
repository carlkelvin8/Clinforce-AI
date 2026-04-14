<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\EmployerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TrustSafetyController extends ApiController
{
    private function requireAdmin(): User
    {
        $u = $this->requireAuth();
        if ($u->role !== 'admin') abort(403, 'Admin only');
        return $u;
    }

    // ── Trust & Safety Dashboard ─────────────────────────────────────────
    public function dashboard(): JsonResponse
    {
        $this->requireAdmin();

        $pendingIdentity   = DB::table('identity_verifications')->where('verification_status', 'pending')->count();
        $pendingReports    = DB::table('content_reports')->where('status', 'pending')->count();
        $pendingModeration = DB::table('moderation_queue')->where('status', 'queued')->count();
        $fraudAlerts       = DB::table('fraud_detection_logs')->where('status', 'pending')->count();
        $redFlags          = DB::table('employer_red_flags')->where('status', 'reported')->count();
        $pendingReviews    = DB::table('employer_interview_reviews')->where('status', 'pending')->count();

        $recentReports = DB::table('content_reports')
            ->join('users', 'users.id', '=', 'content_reports.reported_by_user_id')
            ->select('content_reports.*', 'users.email as reporter_email')
            ->orderByDesc('content_reports.id')
            ->limit(5)->get();

        $recentFraud = DB::table('fraud_detection_logs')
            ->leftJoin('users', 'users.id', '=', 'fraud_detection_logs.user_id')
            ->select('fraud_detection_logs.*', 'users.email as user_email')
            ->orderByDesc('fraud_detection_logs.id')
            ->limit(5)->get();

        return $this->ok([
            'stats' => [
                'pending_identity_verifications' => $pendingIdentity,
                'pending_reports'                => $pendingReports,
                'pending_moderation'             => $pendingModeration,
                'fraud_alerts'                   => $fraudAlerts,
                'employer_red_flags'             => $redFlags,
                'pending_employer_reviews'       => $pendingReviews,
            ],
            'recent_reports' => $recentReports,
            'recent_fraud'   => $recentFraud,
        ]);
    }

    // ── Identity Verifications ───────────────────────────────────────────
    public function identityVerifications(Request $request): JsonResponse
    {
        $this->requireAdmin();

        $status = $request->query('status');
        $q      = trim((string) $request->query('q', ''));

        $query = DB::table('identity_verifications')
            ->leftJoin('users', 'users.id', '=', 'identity_verifications.user_id')
            ->select('identity_verifications.*', 'users.email as user_email', 'users.role as user_role')
            ->orderByDesc('identity_verifications.id');

        if ($status) $query->where('identity_verifications.verification_status', $status);
        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('users.email', 'like', "%{$q}%")
                   ->orWhere('identity_verifications.extracted_name', 'like', "%{$q}%")
                   ->orWhere('identity_verifications.document_number', 'like', "%{$q}%");
            });
        }

        $paginated = $query->paginate(20);
        return $this->ok($paginated);
    }

    public function reviewIdentityVerification(Request $request, int $id): JsonResponse
    {
        $this->requireAdmin();
        $admin = $this->requireAuth();

        $data = $request->validate([
            'status'           => 'required|in:verified,rejected',
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $updated = DB::table('identity_verifications')->where('id', $id)->update([
            'verification_status' => $data['status'],
            'rejection_reason'    => $data['rejection_reason'] ?? null,
            'verified_at'         => $data['status'] === 'verified' ? now() : null,
            'updated_at'          => now(),
        ]);

        if (!$updated) return $this->fail('Verification not found', null, 404);

        return $this->ok(['message' => 'Verification ' . $data['status']]);
    }

    // ── Fraud Detection ──────────────────────────────────────────────────
    public function fraudLogs(Request $request): JsonResponse
    {
        $this->requireAdmin();

        $status = $request->query('status');
        $q      = trim((string) $request->query('q', ''));

        $query = DB::table('fraud_detection_logs')
            ->leftJoin('users', 'users.id', '=', 'fraud_detection_logs.user_id')
            ->select('fraud_detection_logs.*', 'users.email as user_email')
            ->orderByDesc('fraud_detection_logs.id');

        if ($status) $query->where('fraud_detection_logs.status', $status);
        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('users.email', 'like', "%{$q}%")
                   ->orWhere('fraud_detection_logs.fraud_type', 'like', "%{$q}%");
            });
        }

        return $this->ok($query->paginate(20));
    }

    public function updateFraudLog(Request $request, int $id): JsonResponse
    {
        $this->requireAdmin();
        $admin = $this->requireAuth();

        $data = $request->validate([
            'status'              => 'required|in:investigating,confirmed,false_positive,resolved',
            'investigation_notes' => 'nullable|string|max:2000',
        ]);

        DB::table('fraud_detection_logs')->where('id', $id)->update([
            'status'                   => $data['status'],
            'investigation_notes'      => $data['investigation_notes'] ?? null,
            'investigated_by_user_id'  => $admin->id,
            'investigated_at'          => now(),
            'updated_at'               => now(),
        ]);

        return $this->ok(['message' => 'Fraud log updated']);
    }

    // ── Employer Trust Scores ────────────────────────────────────────────
    public function employerTrustScores(Request $request): JsonResponse
    {
        $this->requireAdmin();

        $q = trim((string) $request->query('q', ''));

        $query = DB::table('employer_trust_scores')
            ->join('users', 'users.id', '=', 'employer_trust_scores.employer_user_id')
            ->leftJoin('employer_profiles', 'employer_profiles.user_id', '=', 'employer_trust_scores.employer_user_id')
            ->select(
                'employer_trust_scores.*',
                'users.email',
                'employer_profiles.business_name'
            )
            ->orderByDesc('employer_trust_scores.overall_score');

        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('users.email', 'like', "%{$q}%")
                   ->orWhere('employer_profiles.business_name', 'like', "%{$q}%");
            });
        }

        return $this->ok($query->paginate(20));
    }

    // ── Employer Interview Reviews ────────────────────────────────────────
    public function employerReviews(Request $request): JsonResponse
    {
        $this->requireAdmin();

        $status = $request->query('status', 'pending');

        $query = DB::table('employer_interview_reviews')
            ->join('users as employer', 'employer.id', '=', 'employer_interview_reviews.employer_user_id')
            ->join('users as applicant', 'applicant.id', '=', 'employer_interview_reviews.applicant_user_id')
            ->leftJoin('employer_profiles', 'employer_profiles.user_id', '=', 'employer_interview_reviews.employer_user_id')
            ->select(
                'employer_interview_reviews.*',
                'employer.email as employer_email',
                'employer_profiles.business_name',
                'applicant.email as applicant_email'
            )
            ->orderByDesc('employer_interview_reviews.id');

        if ($status) $query->where('employer_interview_reviews.status', $status);

        return $this->ok($query->paginate(20));
    }

    public function moderateEmployerReview(Request $request, int $id): JsonResponse
    {
        $this->requireAdmin();

        $data = $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        DB::table('employer_interview_reviews')->where('id', $id)->update([
            'status'     => $data['status'],
            'updated_at' => now(),
        ]);

        return $this->ok(['message' => 'Review ' . $data['status']]);
    }

    // ── Employer Red Flags ───────────────────────────────────────────────
    public function redFlags(Request $request): JsonResponse
    {
        $this->requireAdmin();

        $status = $request->query('status');

        $query = DB::table('employer_red_flags')
            ->join('users as employer', 'employer.id', '=', 'employer_red_flags.employer_user_id')
            ->leftJoin('employer_profiles', 'employer_profiles.user_id', '=', 'employer_red_flags.employer_user_id')
            ->leftJoin('users as reporter', 'reporter.id', '=', 'employer_red_flags.reported_by_user_id')
            ->select(
                'employer_red_flags.*',
                'employer.email as employer_email',
                'employer_profiles.business_name',
                'reporter.email as reporter_email'
            )
            ->orderByDesc('employer_red_flags.id');

        if ($status) $query->where('employer_red_flags.status', $status);

        return $this->ok($query->paginate(20));
    }

    public function updateRedFlag(Request $request, int $id): JsonResponse
    {
        $this->requireAdmin();
        $admin = $this->requireAuth();

        $data = $request->validate([
            'status'           => 'required|in:under_review,confirmed,dismissed',
            'resolution_notes' => 'nullable|string|max:2000',
        ]);

        DB::table('employer_red_flags')->where('id', $id)->update([
            'status'                  => $data['status'],
            'resolution_notes'        => $data['resolution_notes'] ?? null,
            'investigated_by_user_id' => $admin->id,
            'resolved_at'             => in_array($data['status'], ['confirmed', 'dismissed']) ? now() : null,
            'updated_at'              => now(),
        ]);

        return $this->ok(['message' => 'Red flag updated']);
    }

    // ── Candidate submits employer review ────────────────────────────────
    public function submitEmployerReview(Request $request, int $employerUserId): JsonResponse
    {
        $u = $this->requireAuth();
        if ($u->role !== 'applicant') return $this->fail('Candidates only', null, 403);

        $data = $request->validate([
            'interview_id'           => 'nullable|exists:interviews,id',
            'job_id'                 => 'nullable|exists:jobs,id',
            'overall_rating'         => 'required|numeric|min:1|max:5',
            'professionalism_rating' => 'nullable|numeric|min:1|max:5',
            'communication_rating'   => 'nullable|numeric|min:1|max:5',
            'transparency_rating'    => 'nullable|numeric|min:1|max:5',
            'comments'               => 'nullable|string|max:2000',
            'tags'                   => 'nullable|array',
            'would_recommend'        => 'boolean',
            'is_anonymous'           => 'boolean',
        ]);

        // Prevent duplicate review for same interview
        if (!empty($data['interview_id'])) {
            $exists = DB::table('employer_interview_reviews')
                ->where('applicant_user_id', $u->id)
                ->where('interview_id', $data['interview_id'])
                ->exists();
            if ($exists) return $this->fail('You already reviewed this interview', null, 409);
        }

        $id = DB::table('employer_interview_reviews')->insertGetId([
            'employer_user_id'       => $employerUserId,
            'applicant_user_id'      => $u->id,
            'job_id'                 => $data['job_id'] ?? null,
            'interview_id'           => $data['interview_id'] ?? null,
            'overall_rating'         => $data['overall_rating'],
            'professionalism_rating' => $data['professionalism_rating'] ?? null,
            'communication_rating'   => $data['communication_rating'] ?? null,
            'transparency_rating'    => $data['transparency_rating'] ?? null,
            'comments'               => $data['comments'] ?? null,
            'tags'                   => isset($data['tags']) ? json_encode($data['tags']) : null,
            'would_recommend'        => $data['would_recommend'] ?? true,
            'is_anonymous'           => $data['is_anonymous'] ?? false,
            'status'                 => 'pending',
            'ip_address'             => $request->ip(),
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        return $this->ok(['id' => $id, 'message' => 'Review submitted for moderation'], 'Review submitted', 201);
    }

    // ── Report content (any user) ────────────────────────────────────────
    public function reportContent(Request $request): JsonResponse
    {
        $u = $this->requireAuth();

        $data = $request->validate([
            'reportable_type' => 'required|string|in:job,user,message',
            'reportable_id'   => 'required|integer',
            'reason'          => 'required|in:inappropriate,spam,scam,harassment,discrimination,fake,offensive,other',
            'description'     => 'nullable|string|max:2000',
        ]);

        $typeMap = [
            'job'     => 'App\\Models\\Job',
            'user'    => 'App\\Models\\User',
            'message' => 'App\\Models\\Message',
        ];

        // Prevent duplicate pending report
        $exists = DB::table('content_reports')
            ->where('reported_by_user_id', $u->id)
            ->where('reportable_type', $typeMap[$data['reportable_type']])
            ->where('reportable_id', $data['reportable_id'])
            ->where('status', 'pending')
            ->exists();

        if ($exists) return $this->fail('You already have a pending report for this content', null, 409);

        $id = DB::table('content_reports')->insertGetId([
            'reported_by_user_id' => $u->id,
            'reportable_type'     => $typeMap[$data['reportable_type']],
            'reportable_id'       => $data['reportable_id'],
            'reason'              => $data['reason'],
            'description'         => $data['description'] ?? null,
            'severity'            => 'medium',
            'status'              => 'pending',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        return $this->ok(['id' => $id], 'Report submitted', 201);
    }
}

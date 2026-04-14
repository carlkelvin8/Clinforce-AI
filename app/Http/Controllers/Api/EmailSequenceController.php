<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmailSequenceController extends ApiController
{
    private function requireEmployer(): User
    {
        $u = $this->requireAuth();
        if (!in_array($u->role, ['employer', 'agency', 'admin'], true)) {
            abort(403, 'Employer only');
        }
        return $u;
    }

    // ── Email Sequences ──────────────────────────────────────────────────
    public function getSequences(Request $request): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $sequences = DB::table('email_sequences')
            ->where('employer_user_id', $u->id)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        // Add step counts
        foreach ($sequences as $sequence) {
            $sequence->steps_count = DB::table('email_sequence_steps')
                ->where('sequence_id', $sequence->id)
                ->where('is_active', true)
                ->count();
            
            $sequence->active_enrollments = DB::table('email_sequence_enrollments')
                ->where('sequence_id', $sequence->id)
                ->where('status', 'active')
                ->count();
        }

        return $this->ok($sequences);
    }

    public function createSequence(Request $request): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:welcome,nurture,re_engagement,follow_up,onboarding,rejection',
            'trigger_event' => 'required|in:application_submitted,profile_created,interview_completed,offer_sent,hired,inactive_period,custom',
            'trigger_conditions' => 'nullable|array',
            'target_audience' => 'nullable|array',
        ]);

        $id = DB::table('email_sequences')->insertGetId([
            'employer_user_id' => $u->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'trigger_event' => $data['trigger_event'],
            'trigger_conditions' => isset($data['trigger_conditions']) ? json_encode($data['trigger_conditions']) : null,
            'target_audience' => isset($data['target_audience']) ? json_encode($data['target_audience']) : null,
            'is_active' => true,
            'total_emails' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->ok(['id' => $id], 'Email sequence created', 201);
    }

    public function updateSequence(Request $request, int $sequenceId): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $sequence = DB::table('email_sequences')
            ->where('id', $sequenceId)
            ->where('employer_user_id', $u->id)
            ->first();

        if (!$sequence) return $this->fail('Email sequence not found', null, 404);

        $data = $request->validate([
            'name' => 'string|max:200',
            'description' => 'nullable|string|max:1000',
            'trigger_conditions' => 'nullable|array',
            'target_audience' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $updateData = array_filter([
            'name' => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'trigger_conditions' => isset($data['trigger_conditions']) ? json_encode($data['trigger_conditions']) : null,
            'target_audience' => isset($data['target_audience']) ? json_encode($data['target_audience']) : null,
            'is_active' => $data['is_active'] ?? null,
            'updated_at' => now(),
        ], fn($value) => $value !== null);

        DB::table('email_sequences')->where('id', $sequenceId)->update($updateData);

        return $this->ok(null, 'Email sequence updated');
    }

    public function deleteSequence(int $sequenceId): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $deleted = DB::table('email_sequences')
            ->where('id', $sequenceId)
            ->where('employer_user_id', $u->id)
            ->delete();

        if (!$deleted) return $this->fail('Email sequence not found', null, 404);

        return $this->ok(null, 'Email sequence deleted');
    }

    // ── Email Sequence Steps ─────────────────────────────────────────────
    public function getSequenceSteps(int $sequenceId): JsonResponse
    {
        $u = $this->requireEmployer();
        
        // Verify sequence ownership
        $sequence = DB::table('email_sequences')
            ->where('id', $sequenceId)
            ->where('employer_user_id', $u->id)
            ->first();

        if (!$sequence) return $this->fail('Email sequence not found', null, 404);

        $steps = DB::table('email_sequence_steps')
            ->where('sequence_id', $sequenceId)
            ->orderBy('step_number')
            ->get();

        return $this->ok($steps);
    }

    public function createSequenceStep(Request $request, int $sequenceId): JsonResponse
    {
        $u = $this->requireEmployer();
        
        // Verify sequence ownership
        $sequence = DB::table('email_sequences')
            ->where('id', $sequenceId)
            ->where('employer_user_id', $u->id)
            ->first();

        if (!$sequence) return $this->fail('Email sequence not found', null, 404);

        $data = $request->validate([
            'step_number' => 'required|integer|min:1',
            'subject' => 'required|string|max:200',
            'body_template' => 'required|string',
            'delay_hours' => 'integer|min:0|max:8760', // Max 1 year
            'send_conditions' => 'nullable|array',
        ]);

        $id = DB::table('email_sequence_steps')->insertGetId([
            'sequence_id' => $sequenceId,
            'step_number' => $data['step_number'],
            'subject' => $data['subject'],
            'body_template' => $data['body_template'],
            'delay_hours' => $data['delay_hours'] ?? 0,
            'send_conditions' => isset($data['send_conditions']) ? json_encode($data['send_conditions']) : null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update total emails count
        $totalSteps = DB::table('email_sequence_steps')
            ->where('sequence_id', $sequenceId)
            ->where('is_active', true)
            ->count();

        DB::table('email_sequences')
            ->where('id', $sequenceId)
            ->update(['total_emails' => $totalSteps]);

        return $this->ok(['id' => $id], 'Email step created', 201);
    }

    public function updateSequenceStep(Request $request, int $sequenceId, int $stepId): JsonResponse
    {
        $u = $this->requireEmployer();
        
        // Verify sequence ownership
        $sequence = DB::table('email_sequences')
            ->where('id', $sequenceId)
            ->where('employer_user_id', $u->id)
            ->first();

        if (!$sequence) return $this->fail('Email sequence not found', null, 404);

        $step = DB::table('email_sequence_steps')
            ->where('id', $stepId)
            ->where('sequence_id', $sequenceId)
            ->first();

        if (!$step) return $this->fail('Email step not found', null, 404);

        $data = $request->validate([
            'subject' => 'string|max:200',
            'body_template' => 'string',
            'delay_hours' => 'integer|min:0|max:8760',
            'send_conditions' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $updateData = array_filter([
            'subject' => $data['subject'] ?? null,
            'body_template' => $data['body_template'] ?? null,
            'delay_hours' => $data['delay_hours'] ?? null,
            'send_conditions' => isset($data['send_conditions']) ? json_encode($data['send_conditions']) : null,
            'is_active' => $data['is_active'] ?? null,
            'updated_at' => now(),
        ], fn($value) => $value !== null);

        DB::table('email_sequence_steps')->where('id', $stepId)->update($updateData);

        return $this->ok(null, 'Email step updated');
    }

    public function deleteSequenceStep(int $sequenceId, int $stepId): JsonResponse
    {
        $u = $this->requireEmployer();
        
        // Verify sequence ownership
        $sequence = DB::table('email_sequences')
            ->where('id', $sequenceId)
            ->where('employer_user_id', $u->id)
            ->first();

        if (!$sequence) return $this->fail('Email sequence not found', null, 404);

        $deleted = DB::table('email_sequence_steps')
            ->where('id', $stepId)
            ->where('sequence_id', $sequenceId)
            ->delete();

        if (!$deleted) return $this->fail('Email step not found', null, 404);

        // Update total emails count
        $totalSteps = DB::table('email_sequence_steps')
            ->where('sequence_id', $sequenceId)
            ->where('is_active', true)
            ->count();

        DB::table('email_sequences')
            ->where('id', $sequenceId)
            ->update(['total_emails' => $totalSteps]);

        return $this->ok(null, 'Email step deleted');
    }

    // ── Email Enrollments ────────────────────────────────────────────────
    public function enrollUser(Request $request): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $data = $request->validate([
            'sequence_id' => 'required|exists:email_sequences,id',
            'user_id' => 'required|exists:users,id',
            'application_id' => 'nullable|exists:job_applications,id',
            'context_data' => 'nullable|array',
        ]);

        // Verify sequence ownership
        $sequence = DB::table('email_sequences')
            ->where('id', $data['sequence_id'])
            ->where('employer_user_id', $u->id)
            ->first();

        if (!$sequence) return $this->fail('Email sequence not found', null, 404);

        // Check if already enrolled
        $existing = DB::table('email_sequence_enrollments')
            ->where('sequence_id', $data['sequence_id'])
            ->where('user_id', $data['user_id'])
            ->where('application_id', $data['application_id'])
            ->where('status', 'active')
            ->first();

        if ($existing) return $this->fail('User already enrolled in this sequence', null, 409);

        // Get first step to calculate next email time
        $firstStep = DB::table('email_sequence_steps')
            ->where('sequence_id', $data['sequence_id'])
            ->where('is_active', true)
            ->orderBy('step_number')
            ->first();

        $nextEmailAt = $firstStep ? now()->addHours($firstStep->delay_hours) : null;

        $id = DB::table('email_sequence_enrollments')->insertGetId([
            'sequence_id' => $data['sequence_id'],
            'user_id' => $data['user_id'],
            'application_id' => $data['application_id'] ?? null,
            'status' => 'active',
            'current_step' => 0,
            'enrolled_at' => now(),
            'next_email_at' => $nextEmailAt,
            'context_data' => isset($data['context_data']) ? json_encode($data['context_data']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->ok(['id' => $id], 'User enrolled in email sequence', 201);
    }

    public function getEnrollments(Request $request): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $sequenceId = $request->query('sequence_id');
        $status = $request->query('status', 'active');

        $query = DB::table('email_sequence_enrollments')
            ->join('email_sequences', 'email_sequences.id', '=', 'email_sequence_enrollments.sequence_id')
            ->join('users', 'users.id', '=', 'email_sequence_enrollments.user_id')
            ->where('email_sequences.employer_user_id', $u->id)
            ->where('email_sequence_enrollments.status', $status);

        if ($sequenceId) {
            $query->where('email_sequence_enrollments.sequence_id', $sequenceId);
        }

        $enrollments = $query
            ->select([
                'email_sequence_enrollments.*',
                'email_sequences.name as sequence_name',
                'users.name as user_name',
                'users.email as user_email'
            ])
            ->orderByDesc('email_sequence_enrollments.enrolled_at')
            ->get();

        return $this->ok($enrollments);
    }

    public function pauseEnrollment(int $enrollmentId): JsonResponse
    {
        $u = $this->requireEmployer();
        
        // Verify enrollment ownership
        $enrollment = DB::table('email_sequence_enrollments')
            ->join('email_sequences', 'email_sequences.id', '=', 'email_sequence_enrollments.sequence_id')
            ->where('email_sequence_enrollments.id', $enrollmentId)
            ->where('email_sequences.employer_user_id', $u->id)
            ->first();

        if (!$enrollment) return $this->fail('Enrollment not found', null, 404);

        DB::table('email_sequence_enrollments')
            ->where('id', $enrollmentId)
            ->update([
                'status' => 'paused',
                'updated_at' => now(),
            ]);

        return $this->ok(null, 'Enrollment paused');
    }

    public function resumeEnrollment(int $enrollmentId): JsonResponse
    {
        $u = $this->requireEmployer();
        
        // Verify enrollment ownership
        $enrollment = DB::table('email_sequence_enrollments')
            ->join('email_sequences', 'email_sequences.id', '=', 'email_sequence_enrollments.sequence_id')
            ->where('email_sequence_enrollments.id', $enrollmentId)
            ->where('email_sequences.employer_user_id', $u->id)
            ->first();

        if (!$enrollment) return $this->fail('Enrollment not found', null, 404);

        DB::table('email_sequence_enrollments')
            ->where('id', $enrollmentId)
            ->update([
                'status' => 'active',
                'updated_at' => now(),
            ]);

        return $this->ok(null, 'Enrollment resumed');
    }

    // ── Email Analytics ──────────────────────────────────────────────────
    public function getSequenceAnalytics(int $sequenceId): JsonResponse
    {
        $u = $this->requireEmployer();
        
        // Verify sequence ownership
        $sequence = DB::table('email_sequences')
            ->where('id', $sequenceId)
            ->where('employer_user_id', $u->id)
            ->first();

        if (!$sequence) return $this->fail('Email sequence not found', null, 404);

        // Get enrollment stats
        $enrollmentStats = DB::table('email_sequence_enrollments')
            ->where('sequence_id', $sequenceId)
            ->selectRaw('
                COUNT(*) as total_enrollments,
                SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_enrollments,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_enrollments,
                SUM(CASE WHEN status = "paused" THEN 1 ELSE 0 END) as paused_enrollments,
                SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled_enrollments
            ')
            ->first();

        // Get email performance stats
        $emailStats = DB::table('email_sequence_sends')
            ->join('email_sequence_enrollments', 'email_sequence_enrollments.id', '=', 'email_sequence_sends.enrollment_id')
            ->where('email_sequence_enrollments.sequence_id', $sequenceId)
            ->selectRaw('
                COUNT(*) as total_emails_sent,
                SUM(CASE WHEN email_sequence_sends.status = "delivered" THEN 1 ELSE 0 END) as delivered_count,
                SUM(CASE WHEN email_sequence_sends.status = "opened" THEN 1 ELSE 0 END) as opened_count,
                SUM(CASE WHEN email_sequence_sends.status = "clicked" THEN 1 ELSE 0 END) as clicked_count,
                SUM(CASE WHEN email_sequence_sends.status = "bounced" THEN 1 ELSE 0 END) as bounced_count
            ')
            ->first();

        return $this->ok([
            'enrollment_stats' => $enrollmentStats,
            'email_stats' => $emailStats,
            'delivery_rate' => $emailStats->total_emails_sent > 0 ? round(($emailStats->delivered_count / $emailStats->total_emails_sent) * 100, 2) : 0,
            'open_rate' => $emailStats->delivered_count > 0 ? round(($emailStats->opened_count / $emailStats->delivered_count) * 100, 2) : 0,
            'click_rate' => $emailStats->opened_count > 0 ? round(($emailStats->clicked_count / $emailStats->opened_count) * 100, 2) : 0,
        ]);
    }
}
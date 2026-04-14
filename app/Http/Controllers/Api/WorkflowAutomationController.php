<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\JobApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkflowAutomationController extends ApiController
{
    private function requireEmployer(): User
    {
        $u = $this->requireAuth();
        if (!in_array($u->role, ['employer', 'agency', 'admin'], true)) {
            abort(403, 'Employer only');
        }
        return $u;
    }

    // ── Hiring Workflows ─────────────────────────────────────────────────
    public function getWorkflows(Request $request): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $workflows = DB::table('hiring_workflows')
            ->where('employer_user_id', $u->id)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return $this->ok($workflows);
    }

    public function createWorkflow(Request $request): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $data = $request->validate([
            'name' => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'scope' => 'required|in:job,department,global',
            'job_id' => 'nullable|exists:jobs_table,id',
            'department' => 'nullable|string|max:100',
            'stages' => 'required|array',
            'auto_advance_rules' => 'nullable|array',
            'approval_rules' => 'nullable|array',
            'sla_settings' => 'nullable|array',
            'is_default' => 'boolean',
        ]);

        // If setting as default, unset other defaults
        if ($data['is_default'] ?? false) {
            DB::table('hiring_workflows')
                ->where('employer_user_id', $u->id)
                ->update(['is_default' => false]);
        }

        $id = DB::table('hiring_workflows')->insertGetId([
            'employer_user_id' => $u->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'scope' => $data['scope'],
            'job_id' => $data['job_id'] ?? null,
            'department' => $data['department'] ?? null,
            'stages' => json_encode($data['stages']),
            'auto_advance_rules' => isset($data['auto_advance_rules']) ? json_encode($data['auto_advance_rules']) : null,
            'approval_rules' => isset($data['approval_rules']) ? json_encode($data['approval_rules']) : null,
            'sla_settings' => isset($data['sla_settings']) ? json_encode($data['sla_settings']) : null,
            'is_active' => true,
            'is_default' => $data['is_default'] ?? false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->ok(['id' => $id], 'Workflow created', 201);
    }

    public function updateWorkflow(Request $request, int $workflowId): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $workflow = DB::table('hiring_workflows')
            ->where('id', $workflowId)
            ->where('employer_user_id', $u->id)
            ->first();

        if (!$workflow) return $this->fail('Workflow not found', null, 404);

        $data = $request->validate([
            'name' => 'string|max:200',
            'description' => 'nullable|string|max:1000',
            'stages' => 'array',
            'auto_advance_rules' => 'nullable|array',
            'approval_rules' => 'nullable|array',
            'sla_settings' => 'nullable|array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        // If setting as default, unset other defaults
        if (isset($data['is_default']) && $data['is_default']) {
            DB::table('hiring_workflows')
                ->where('employer_user_id', $u->id)
                ->where('id', '!=', $workflowId)
                ->update(['is_default' => false]);
        }

        $updateData = array_filter([
            'name' => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'stages' => isset($data['stages']) ? json_encode($data['stages']) : null,
            'auto_advance_rules' => isset($data['auto_advance_rules']) ? json_encode($data['auto_advance_rules']) : null,
            'approval_rules' => isset($data['approval_rules']) ? json_encode($data['approval_rules']) : null,
            'sla_settings' => isset($data['sla_settings']) ? json_encode($data['sla_settings']) : null,
            'is_active' => $data['is_active'] ?? null,
            'is_default' => $data['is_default'] ?? null,
            'updated_at' => now(),
        ], fn($value) => $value !== null);

        DB::table('hiring_workflows')->where('id', $workflowId)->update($updateData);

        return $this->ok(null, 'Workflow updated');
    }

    public function deleteWorkflow(int $workflowId): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $deleted = DB::table('hiring_workflows')
            ->where('id', $workflowId)
            ->where('employer_user_id', $u->id)
            ->delete();

        if (!$deleted) return $this->fail('Workflow not found', null, 404);

        return $this->ok(null, 'Workflow deleted');
    }

    // ── Stage Transitions ────────────────────────────────────────────────
    public function advanceCandidate(Request $request): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $data = $request->validate([
            'application_id' => 'required|exists:job_applications,id',
            'to_stage' => 'required|string',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Get application and verify ownership
        $application = DB::table('job_applications')
            ->join('jobs_table', 'jobs_table.id', '=', 'job_applications.job_id')
            ->where('job_applications.id', $data['application_id'])
            ->where('jobs_table.owner_user_id', $u->id)
            ->select('job_applications.*', 'jobs_table.title as job_title')
            ->first();

        if (!$application) return $this->fail('Application not found', null, 404);

        // Get current stage
        $currentTransition = DB::table('workflow_stage_transitions')
            ->where('application_id', $data['application_id'])
            ->whereNull('exited_at')
            ->first();

        $fromStage = $currentTransition->to_stage ?? 'applied';

        // Close current stage
        if ($currentTransition) {
            $timeInStage = now()->diffInHours($currentTransition->entered_at);
            DB::table('workflow_stage_transitions')
                ->where('id', $currentTransition->id)
                ->update([
                    'exited_at' => now(),
                    'time_in_stage_hours' => $timeInStage,
                ]);
        }

        // Create new stage transition
        $transitionId = DB::table('workflow_stage_transitions')->insertGetId([
            'application_id' => $data['application_id'],
            'workflow_id' => $currentTransition->workflow_id ?? 1, // Default workflow
            'from_stage' => $fromStage,
            'to_stage' => $data['to_stage'],
            'triggered_by_user_id' => $u->id,
            'trigger_type' => 'manual',
            'notes' => $data['notes'] ?? null,
            'entered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update application status
        DB::table('job_applications')
            ->where('id', $data['application_id'])
            ->update(['status' => $data['to_stage']]);

        // Log automation
        DB::table('workflow_automation_logs')->insert([
            'automation_type' => 'workflow_advance',
            'entity_type' => 'Application',
            'entity_id' => $data['application_id'],
            'triggered_by_user_id' => $u->id,
            'action_taken' => "Advanced from {$fromStage} to {$data['to_stage']}",
            'status' => 'success',
            'executed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->ok(['transition_id' => $transitionId], 'Candidate advanced');
    }

    public function getStageHistory(int $applicationId): JsonResponse
    {
        $u = $this->requireEmployer();
        
        // Verify application ownership
        $application = DB::table('job_applications')
            ->join('jobs_table', 'jobs_table.id', '=', 'job_applications.job_id')
            ->where('job_applications.id', $applicationId)
            ->where('jobs_table.owner_user_id', $u->id)
            ->first();

        if (!$application) return $this->fail('Application not found', null, 404);

        $history = DB::table('workflow_stage_transitions')
            ->leftJoin('users', 'users.id', '=', 'workflow_stage_transitions.triggered_by_user_id')
            ->where('application_id', $applicationId)
            ->select([
                'workflow_stage_transitions.*',
                'users.name as triggered_by_name'
            ])
            ->orderBy('entered_at')
            ->get();

        return $this->ok($history);
    }

    // ── SLA Tracking ─────────────────────────────────────────────────────
    public function getSlaViolations(Request $request): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $violations = DB::table('sla_violations')
            ->join('job_applications', 'job_applications.id', '=', 'sla_violations.application_id')
            ->join('jobs_table', 'jobs_table.id', '=', 'job_applications.job_id')
            ->join('users', 'users.id', '=', 'job_applications.applicant_user_id')
            ->where('jobs_table.owner_user_id', $u->id)
            ->where('sla_violations.is_resolved', false)
            ->select([
                'sla_violations.*',
                'jobs_table.title as job_title',
                'users.name as candidate_name',
                'users.email as candidate_email'
            ])
            ->orderByDesc('breached_at')
            ->get();

        return $this->ok($violations);
    }

    public function resolveSlaViolation(Request $request, int $violationId): JsonResponse
    {
        $u = $this->requireEmployer();
        
        $data = $request->validate([
            'resolution_notes' => 'required|string|max:1000',
        ]);

        // Verify violation ownership
        $violation = DB::table('sla_violations')
            ->join('job_applications', 'job_applications.id', '=', 'sla_violations.application_id')
            ->join('jobs_table', 'jobs_table.id', '=', 'job_applications.job_id')
            ->where('sla_violations.id', $violationId)
            ->where('jobs_table.owner_user_id', $u->id)
            ->first();

        if (!$violation) return $this->fail('SLA violation not found', null, 404);

        DB::table('sla_violations')
            ->where('id', $violationId)
            ->update([
                'is_resolved' => true,
                'resolution_notes' => $data['resolution_notes'],
                'resolved_at' => now(),
                'updated_at' => now(),
            ]);

        return $this->ok(null, 'SLA violation resolved');
    }
}
<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkflowAutomationService
{
    /**
     * Process auto-advance rules for an application
     */
    public function processAutoAdvance(int $applicationId): bool
    {
        try {
            // Get application with current workflow
            $application = DB::table('job_applications')
                ->join('jobs_table', 'jobs_table.id', '=', 'job_applications.job_id')
                ->leftJoin('workflow_stage_transitions', function($join) {
                    $join->on('workflow_stage_transitions.application_id', '=', 'job_applications.id')
                         ->whereNull('workflow_stage_transitions.exited_at');
                })
                ->leftJoin('hiring_workflows', 'hiring_workflows.id', '=', 'workflow_stage_transitions.workflow_id')
                ->where('job_applications.id', $applicationId)
                ->select([
                    'job_applications.*',
                    'jobs_table.owner_user_id',
                    'workflow_stage_transitions.to_stage as current_stage',
                    'hiring_workflows.auto_advance_rules',
                    'hiring_workflows.id as workflow_id'
                ])
                ->first();

            if (!$application || !$application->auto_advance_rules) {
                return false;
            }

            $autoAdvanceRules = json_decode($application->auto_advance_rules, true);
            $currentStage = $application->current_stage ?? 'applied';

            // Check if there are rules for the current stage
            if (!isset($autoAdvanceRules[$currentStage])) {
                return false;
            }

            $rules = $autoAdvanceRules[$currentStage];
            $conditionsMet = [];

            // Check each condition
            foreach ($rules['conditions'] as $condition) {
                $met = $this->checkCondition($applicationId, $condition);
                $conditionsMet[$condition['type']] = $met;
                
                if (!$met && $rules['require_all']) {
                    return false; // All conditions must be met
                }
            }

            // If we reach here and require_all is false, check if any condition was met
            if (!$rules['require_all'] && !in_array(true, $conditionsMet)) {
                return false;
            }

            // Advance to next stage
            $nextStage = $rules['next_stage'];
            $this->advanceApplicationStage($applicationId, $currentStage, $nextStage, 'auto_advance', $conditionsMet);

            return true;

        } catch (\Exception $e) {
            Log::error('Auto-advance processing failed', [
                'application_id' => $applicationId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if a specific condition is met
     */
    private function checkCondition(int $applicationId, array $condition): bool
    {
        switch ($condition['type']) {
            case 'time_in_stage':
                return $this->checkTimeInStage($applicationId, $condition['hours']);
            
            case 'document_uploaded':
                return $this->checkDocumentUploaded($applicationId, $condition['document_type']);
            
            case 'assessment_completed':
                return $this->checkAssessmentCompleted($applicationId, $condition['assessment_id']);
            
            case 'interview_scheduled':
                return $this->checkInterviewScheduled($applicationId);
            
            case 'reference_received':
                return $this->checkReferenceReceived($applicationId, $condition['count'] ?? 1);
            
            default:
                return false;
        }
    }

    private function checkTimeInStage(int $applicationId, int $hours): bool
    {
        $transition = DB::table('workflow_stage_transitions')
            ->where('application_id', $applicationId)
            ->whereNull('exited_at')
            ->first();

        if (!$transition) return false;

        $hoursInStage = now()->diffInHours($transition->entered_at);
        return $hoursInStage >= $hours;
    }

    private function checkDocumentUploaded(int $applicationId, string $documentType): bool
    {
        return DB::table('documents')
            ->join('job_applications', 'job_applications.applicant_user_id', '=', 'documents.user_id')
            ->where('job_applications.id', $applicationId)
            ->where('documents.doc_type', $documentType)
            ->where('documents.status', 'active')
            ->exists();
    }

    private function checkAssessmentCompleted(int $applicationId, int $assessmentId): bool
    {
        return DB::table('assessment_responses')
            ->where('application_id', $applicationId)
            ->where('assessment_id', $assessmentId)
            ->where('status', 'completed')
            ->exists();
    }

    private function checkInterviewScheduled(int $applicationId): bool
    {
        return DB::table('interviews')
            ->where('application_id', $applicationId)
            ->where('status', 'scheduled')
            ->exists();
    }

    private function checkReferenceReceived(int $applicationId, int $count): bool
    {
        $receivedCount = DB::table('reference_checks')
            ->where('application_id', $applicationId)
            ->where('status', 'completed')
            ->count();

        return $receivedCount >= $count;
    }

    /**
     * Advance application to next stage
     */
    public function advanceApplicationStage(int $applicationId, string $fromStage, string $toStage, string $triggerType = 'manual', array $conditionsMet = []): void
    {
        // Close current stage
        $currentTransition = DB::table('workflow_stage_transitions')
            ->where('application_id', $applicationId)
            ->whereNull('exited_at')
            ->first();

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
        DB::table('workflow_stage_transitions')->insert([
            'application_id' => $applicationId,
            'workflow_id' => $currentTransition->workflow_id ?? 1,
            'from_stage' => $fromStage,
            'to_stage' => $toStage,
            'triggered_by_user_id' => null, // System triggered
            'trigger_type' => $triggerType,
            'conditions_met' => json_encode($conditionsMet),
            'entered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update application status
        DB::table('job_applications')
            ->where('id', $applicationId)
            ->update(['status' => $toStage]);

        // Log automation
        DB::table('workflow_automation_logs')->insert([
            'automation_type' => 'workflow_advance',
            'entity_type' => 'Application',
            'entity_id' => $applicationId,
            'action_taken' => "Auto-advanced from {$fromStage} to {$toStage}",
            'conditions_checked' => json_encode($conditionsMet),
            'status' => 'success',
            'executed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Check and create SLA violations
     */
    public function checkSlaViolations(): void
    {
        // Get all active stage transitions with SLA settings
        $activeTransitions = DB::table('workflow_stage_transitions')
            ->join('hiring_workflows', 'hiring_workflows.id', '=', 'workflow_stage_transitions.workflow_id')
            ->whereNull('workflow_stage_transitions.exited_at')
            ->whereNotNull('hiring_workflows.sla_settings')
            ->select([
                'workflow_stage_transitions.*',
                'hiring_workflows.sla_settings'
            ])
            ->get();

        foreach ($activeTransitions as $transition) {
            $slaSettings = json_decode($transition->sla_settings, true);
            
            if (!isset($slaSettings[$transition->to_stage])) {
                continue;
            }

            $slaHours = $slaSettings[$transition->to_stage]['hours'];
            $hoursInStage = now()->diffInHours($transition->entered_at);

            if ($hoursInStage > $slaHours) {
                // Check if violation already exists
                $existingViolation = DB::table('sla_violations')
                    ->where('application_id', $transition->application_id)
                    ->where('stage_name', $transition->to_stage)
                    ->where('is_resolved', false)
                    ->first();

                if (!$existingViolation) {
                    $breachHours = $hoursInStage - $slaHours;
                    $severity = $this->calculateSeverity($breachHours, $slaHours);

                    DB::table('sla_violations')->insert([
                        'application_id' => $transition->application_id,
                        'workflow_id' => $transition->workflow_id,
                        'stage_name' => $transition->to_stage,
                        'sla_hours' => $slaHours,
                        'actual_hours' => $hoursInStage,
                        'breach_hours' => $breachHours,
                        'severity' => $severity,
                        'breached_at' => now()->subHours($breachHours),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    private function calculateSeverity(int $breachHours, int $slaHours): string
    {
        $breachPercentage = ($breachHours / $slaHours) * 100;

        if ($breachPercentage >= 100) return 'critical';
        if ($breachPercentage >= 50) return 'major';
        return 'minor';
    }

    /**
     * Process approval workflows
     */
    public function processApprovalRequest(int $requestId, int $approverId, string $action, string $comments = null): bool
    {
        try {
            $request = DB::table('approval_requests')->where('id', $requestId)->first();
            if (!$request) return false;

            $approvalChain = json_decode($request->approval_chain, true);
            $currentStep = $request->current_step;

            // Verify approver is correct for current step
            if ($approvalChain[$currentStep]['approver_id'] != $approverId) {
                return false;
            }

            // Record approval action
            DB::table('approval_actions')->insert([
                'approval_request_id' => $requestId,
                'approver_user_id' => $approverId,
                'step_number' => $currentStep,
                'action' => $action,
                'comments' => $comments,
                'acted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($action === 'rejected') {
                // Reject the entire request
                DB::table('approval_requests')
                    ->where('id', $requestId)
                    ->update([
                        'status' => 'rejected',
                        'rejected_at' => now(),
                        'rejection_reason' => $comments,
                    ]);
            } else {
                // Move to next step or complete
                $nextStep = $currentStep + 1;
                
                if ($nextStep >= count($approvalChain)) {
                    // All approvals complete
                    DB::table('approval_requests')
                        ->where('id', $requestId)
                        ->update([
                            'status' => 'approved',
                            'approved_at' => now(),
                            'current_step' => $nextStep,
                        ]);
                } else {
                    // Move to next approver
                    DB::table('approval_requests')
                        ->where('id', $requestId)
                        ->update([
                            'current_step' => $nextStep,
                            'current_approver_id' => $approvalChain[$nextStep]['approver_id'],
                        ]);
                }
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Approval processing failed', [
                'request_id' => $requestId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
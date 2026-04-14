<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailSequenceService
{
    /**
     * Enroll user in email sequence based on trigger event
     */
    public function enrollUserByTrigger(string $triggerEvent, int $userId, array $context = []): void
    {
        try {
            // Find active sequences for this trigger
            $sequences = DB::table('email_sequences')
                ->where('trigger_event', $triggerEvent)
                ->where('is_active', true)
                ->get();

            foreach ($sequences as $sequence) {
                // Check if user meets target audience criteria
                if (!$this->userMatchesAudience($userId, $sequence->target_audience, $context)) {
                    continue;
                }

                // Check trigger conditions
                if (!$this->checkTriggerConditions($userId, $sequence->trigger_conditions, $context)) {
                    continue;
                }

                // Check if already enrolled
                $existingEnrollment = DB::table('email_sequence_enrollments')
                    ->where('sequence_id', $sequence->id)
                    ->where('user_id', $userId)
                    ->where('status', 'active')
                    ->first();

                if ($existingEnrollment) {
                    continue;
                }

                // Enroll user
                $this->enrollUser($sequence->id, $userId, $context);
            }

        } catch (\Exception $e) {
            Log::error('Email sequence enrollment failed', [
                'trigger_event' => $triggerEvent,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Enroll user in specific sequence
     */
    public function enrollUser(int $sequenceId, int $userId, array $context = []): int
    {
        // Get first step to calculate next email time
        $firstStep = DB::table('email_sequence_steps')
            ->where('sequence_id', $sequenceId)
            ->where('is_active', true)
            ->orderBy('step_number')
            ->first();

        $nextEmailAt = $firstStep ? now()->addHours($firstStep->delay_hours) : null;

        $enrollmentId = DB::table('email_sequence_enrollments')->insertGetId([
            'sequence_id' => $sequenceId,
            'user_id' => $userId,
            'application_id' => $context['application_id'] ?? null,
            'status' => 'active',
            'current_step' => 0,
            'enrolled_at' => now(),
            'next_email_at' => $nextEmailAt,
            'context_data' => json_encode($context),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Log automation
        DB::table('workflow_automation_logs')->insert([
            'automation_type' => 'email_sequence',
            'entity_type' => 'User',
            'entity_id' => $userId,
            'action_taken' => "Enrolled in email sequence: {$sequenceId}",
            'status' => 'success',
            'executed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $enrollmentId;
    }

    /**
     * Process pending email sends
     */
    public function processPendingEmails(): void
    {
        // Get enrollments ready for next email
        $readyEnrollments = DB::table('email_sequence_enrollments')
            ->where('status', 'active')
            ->where('next_email_at', '<=', now())
            ->whereNotNull('next_email_at')
            ->get();

        foreach ($readyEnrollments as $enrollment) {
            $this->sendNextEmail($enrollment->id);
        }
    }

    /**
     * Send next email in sequence
     */
    public function sendNextEmail(int $enrollmentId): bool
    {
        try {
            $enrollment = DB::table('email_sequence_enrollments')
                ->join('email_sequences', 'email_sequences.id', '=', 'email_sequence_enrollments.sequence_id')
                ->join('users', 'users.id', '=', 'email_sequence_enrollments.user_id')
                ->where('email_sequence_enrollments.id', $enrollmentId)
                ->select([
                    'email_sequence_enrollments.*',
                    'email_sequences.name as sequence_name',
                    'users.name as user_name',
                    'users.email as user_email'
                ])
                ->first();

            if (!$enrollment) return false;

            // Get next step
            $nextStepNumber = $enrollment->current_step + 1;
            $step = DB::table('email_sequence_steps')
                ->where('sequence_id', $enrollment->sequence_id)
                ->where('step_number', $nextStepNumber)
                ->where('is_active', true)
                ->first();

            if (!$step) {
                // Sequence completed
                DB::table('email_sequence_enrollments')
                    ->where('id', $enrollmentId)
                    ->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'next_email_at' => null,
                    ]);
                return true;
            }

            // Check send conditions
            if (!$this->checkSendConditions($enrollment->user_id, $step->send_conditions)) {
                // Skip this step, move to next
                $this->scheduleNextEmail($enrollmentId, $nextStepNumber);
                return true;
            }

            // Prepare email content
            $contextData = json_decode($enrollment->context_data, true) ?? [];
            $subject = $this->replacePlaceholders($step->subject, $enrollment, $contextData);
            $body = $this->replacePlaceholders($step->body_template, $enrollment, $contextData);

            // Create send record
            $sendId = DB::table('email_sequence_sends')->insertGetId([
                'enrollment_id' => $enrollmentId,
                'step_id' => $step->id,
                'recipient_email' => $enrollment->user_email,
                'subject' => $subject,
                'body_html' => $body,
                'status' => 'queued',
                'queued_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Send email (in production, use a queue)
            $this->sendEmail($sendId, $enrollment->user_email, $subject, $body);

            // Update enrollment for next step
            $this->scheduleNextEmail($enrollmentId, $nextStepNumber);

            return true;

        } catch (\Exception $e) {
            Log::error('Email sequence send failed', [
                'enrollment_id' => $enrollmentId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Schedule next email in sequence
     */
    private function scheduleNextEmail(int $enrollmentId, int $currentStep): void
    {
        $enrollment = DB::table('email_sequence_enrollments')->where('id', $enrollmentId)->first();
        
        // Get next step
        $nextStep = DB::table('email_sequence_steps')
            ->where('sequence_id', $enrollment->sequence_id)
            ->where('step_number', $currentStep + 1)
            ->where('is_active', true)
            ->first();

        $nextEmailAt = $nextStep ? now()->addHours($nextStep->delay_hours) : null;

        DB::table('email_sequence_enrollments')
            ->where('id', $enrollmentId)
            ->update([
                'current_step' => $currentStep,
                'next_email_at' => $nextEmailAt,
                'updated_at' => now(),
            ]);
    }

    /**
     * Send actual email
     */
    private function sendEmail(int $sendId, string $email, string $subject, string $body): void
    {
        try {
            // In production, use Laravel Mail or a service like SendGrid
            // For now, just update status
            DB::table('email_sequence_sends')
                ->where('id', $sendId)
                ->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);

            Log::info('Email sequence email sent', [
                'send_id' => $sendId,
                'email' => $email,
                'subject' => $subject
            ]);

        } catch (\Exception $e) {
            DB::table('email_sequence_sends')
                ->where('id', $sendId)
                ->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

            Log::error('Email sequence send failed', [
                'send_id' => $sendId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if user matches target audience
     */
    private function userMatchesAudience(int $userId, ?string $targetAudience, array $context): bool
    {
        if (!$targetAudience) return true;

        $audience = json_decode($targetAudience, true);
        if (!$audience) return true;

        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) return false;

        // Check role
        if (isset($audience['roles']) && !in_array($user->role, $audience['roles'])) {
            return false;
        }

        // Check other criteria as needed
        return true;
    }

    /**
     * Check trigger conditions
     */
    private function checkTriggerConditions(int $userId, ?string $triggerConditions, array $context): bool
    {
        if (!$triggerConditions) return true;

        $conditions = json_decode($triggerConditions, true);
        if (!$conditions) return true;

        // Implement condition checking logic
        return true;
    }

    /**
     * Check send conditions for a step
     */
    private function checkSendConditions(int $userId, ?string $sendConditions): bool
    {
        if (!$sendConditions) return true;

        $conditions = json_decode($sendConditions, true);
        if (!$conditions) return true;

        // Implement send condition checking logic
        return true;
    }

    /**
     * Replace placeholders in email content
     */
    private function replacePlaceholders(string $template, object $enrollment, array $context): string
    {
        $content = $template;
        
        // Replace user data
        $content = str_replace('{{user_name}}', $enrollment->user_name, $content);
        $content = str_replace('{{user_email}}', $enrollment->user_email, $content);
        
        // Replace context data
        foreach ($context as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        
        // Replace system data
        $content = str_replace('{{current_date}}', now()->format('F j, Y'), $content);
        $content = str_replace('{{sequence_name}}', $enrollment->sequence_name, $content);
        
        return $content;
    }

    /**
     * Track email opens (webhook handler)
     */
    public function trackEmailOpen(string $providerMessageId): void
    {
        DB::table('email_sequence_sends')
            ->where('provider_message_id', $providerMessageId)
            ->whereNull('opened_at')
            ->update([
                'status' => 'opened',
                'opened_at' => now(),
            ]);
    }

    /**
     * Track email clicks (webhook handler)
     */
    public function trackEmailClick(string $providerMessageId): void
    {
        DB::table('email_sequence_sends')
            ->where('provider_message_id', $providerMessageId)
            ->whereNull('clicked_at')
            ->update([
                'status' => 'clicked',
                'clicked_at' => now(),
            ]);
    }
}
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }

    private function addIndex(Blueprint $table, string $tableName, array|string $columns, string $name): void
    {
        if ($this->hasIndex($tableName, $name)) {
            return;
        }
        // Ensure all columns exist before adding index
        $cols = is_array($columns) ? $columns : [$columns];
        foreach ($cols as $col) {
            if (!Schema::hasColumn($tableName, $col)) {
                return;
            }
        }
        $table->index($columns, $name);
    }

    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $this->addIndex($table, 'job_applications', 'job_id', 'idx_job_applications_job_id');
            $this->addIndex($table, 'job_applications', 'applicant_user_id', 'idx_job_applications_applicant_user_id');
            $this->addIndex($table, 'job_applications', 'status', 'idx_job_applications_status');
            $this->addIndex($table, 'job_applications', 'created_at', 'idx_job_applications_created_at');
            $this->addIndex($table, 'job_applications', ['job_id', 'status'], 'idx_job_applications_job_status');
        });

        Schema::table('jobs', function (Blueprint $table) {
            $this->addIndex($table, 'jobs', 'owner_user_id', 'idx_jobs_owner_user_id');
            $this->addIndex($table, 'jobs', 'status', 'idx_jobs_status');
            $this->addIndex($table, 'jobs', 'created_at', 'idx_jobs_created_at');
            $this->addIndex($table, 'jobs', ['owner_user_id', 'status'], 'idx_jobs_owner_status');
        });

        Schema::table('messages', function (Blueprint $table) {
            $this->addIndex($table, 'messages', 'conversation_id', 'idx_messages_conversation_id');
            $this->addIndex($table, 'messages', 'sender_user_id', 'idx_messages_sender_user_id');
            $this->addIndex($table, 'messages', 'created_at', 'idx_messages_created_at');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $this->addIndex($table, 'conversations', 'created_at', 'idx_conversations_created_at');
        });

        Schema::table('conversation_participants', function (Blueprint $table) {
            $this->addIndex($table, 'conversation_participants', 'conversation_id', 'idx_conv_participants_conversation_id');
            $this->addIndex($table, 'conversation_participants', 'user_id', 'idx_conv_participants_user_id');
            $this->addIndex($table, 'conversation_participants', ['conversation_id', 'user_id'], 'idx_conv_participants_conv_user');
        });

        Schema::table('interviews', function (Blueprint $table) {
            $this->addIndex($table, 'interviews', 'application_id', 'idx_interviews_application_id');
            $this->addIndex($table, 'interviews', 'scheduled_start', 'idx_interviews_scheduled_start');
            $this->addIndex($table, 'interviews', 'status', 'idx_interviews_status');
        });

        Schema::table('documents', function (Blueprint $table) {
            $this->addIndex($table, 'documents', 'user_id', 'idx_documents_user_id');
            $this->addIndex($table, 'documents', 'doc_type', 'idx_documents_doc_type');
            $this->addIndex($table, 'documents', 'created_at', 'idx_documents_created_at');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $this->addIndex($table, 'subscriptions', 'user_id', 'idx_subscriptions_user_id');
            $this->addIndex($table, 'subscriptions', 'plan_id', 'idx_subscriptions_plan_id');
            $this->addIndex($table, 'subscriptions', 'status', 'idx_subscriptions_status');
            $this->addIndex($table, 'subscriptions', 'end_at', 'idx_subscriptions_end_at');
            $this->addIndex($table, 'subscriptions', ['user_id', 'status'], 'idx_subscriptions_user_status');
        });

        Schema::table('payments', function (Blueprint $table) {
            $this->addIndex($table, 'payments', 'user_id', 'idx_payments_user_id');
            $this->addIndex($table, 'payments', 'subscription_id', 'idx_payments_subscription_id');
            $this->addIndex($table, 'payments', 'status', 'idx_payments_status');
            $this->addIndex($table, 'payments', 'created_at', 'idx_payments_created_at');
        });

        Schema::table('invitations', function (Blueprint $table) {
            $this->addIndex($table, 'invitations', 'employer_id', 'idx_invitations_employer_id');
            $this->addIndex($table, 'invitations', 'candidate_id', 'idx_invitations_candidate_id');
            $this->addIndex($table, 'invitations', 'status', 'idx_invitations_status');
            $this->addIndex($table, 'invitations', 'created_at', 'idx_invitations_created_at');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $this->addIndex($table, 'audit_logs', 'actor_user_id', 'idx_audit_logs_actor_user_id');
            $this->addIndex($table, 'audit_logs', 'action', 'idx_audit_logs_action');
            $this->addIndex($table, 'audit_logs', 'created_at', 'idx_audit_logs_created_at');
        });

        Schema::table('ai_screenings', function (Blueprint $table) {
            $this->addIndex($table, 'ai_screenings', 'application_id', 'idx_ai_screenings_application_id');
            $this->addIndex($table, 'ai_screenings', 'created_at', 'idx_ai_screenings_created_at');
        });
    }

    public function down(): void
    {
        $drops = [
            'job_applications' => ['idx_job_applications_job_id', 'idx_job_applications_applicant_user_id', 'idx_job_applications_status', 'idx_job_applications_created_at', 'idx_job_applications_job_status'],
            'jobs' => ['idx_jobs_owner_user_id', 'idx_jobs_status', 'idx_jobs_created_at', 'idx_jobs_owner_status'],
            'messages' => ['idx_messages_conversation_id', 'idx_messages_sender_user_id', 'idx_messages_created_at'],
            'conversations' => ['idx_conversations_created_at'],
            'conversation_participants' => ['idx_conv_participants_conversation_id', 'idx_conv_participants_user_id', 'idx_conv_participants_conv_user'],
            'interviews' => ['idx_interviews_application_id', 'idx_interviews_scheduled_start', 'idx_interviews_status'],
            'documents' => ['idx_documents_user_id', 'idx_documents_doc_type', 'idx_documents_created_at'],
            'subscriptions' => ['idx_subscriptions_user_id', 'idx_subscriptions_plan_id', 'idx_subscriptions_status', 'idx_subscriptions_end_at', 'idx_subscriptions_user_status'],
            'payments' => ['idx_payments_user_id', 'idx_payments_subscription_id', 'idx_payments_status', 'idx_payments_created_at'],
            'invitations' => ['idx_invitations_employer_id', 'idx_invitations_candidate_id', 'idx_invitations_status', 'idx_invitations_created_at'],
            'audit_logs' => ['idx_audit_logs_actor_user_id', 'idx_audit_logs_action', 'idx_audit_logs_created_at'],
            'ai_screenings' => ['idx_ai_screenings_application_id', 'idx_ai_screenings_created_at'],
        ];

        foreach ($drops as $tableName => $indexes) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $indexes) {
                foreach ($indexes as $index) {
                    if ($this->hasIndex($tableName, $index)) {
                        $table->dropIndex($index);
                    }
                }
            });
        }
    }
};

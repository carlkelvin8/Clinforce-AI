<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Fixes all column mismatches between models and actual DB schema.
 *
 * Changes:
 * 1. audit_logs: rename `meta` -> `metadata`
 * 2. conversation_participants: rename `role` -> `role_at_join`, rename `last_read_message_id` -> `last_read_at` (bigint -> timestamp)
 * 3. job_applications: add `resume_document_id`, `cover_letter_document_id`
 * 4. zoom_filter_settings: add `privacy_filtering`
 * 5. plans: add `stripe_price_id`, `features_json` if missing
 * 6. interviews: ensure model-expected columns exist (scheduled_start, scheduled_end, mode, meeting_link, location_text, cancel_reason, created_by_user_id)
 * 7. verification_requests: ensure `role`, `notes`, `reviewed_by_user_id` exist
 * 8. ai_screenings: ensure `job_id`, `applicant_user_id`, `model_name`, `score`, `suggestions` exist
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        // 1. audit_logs: rename meta -> metadata (drop check constraint first, re-add after)
        if (Schema::hasColumn('audit_logs', 'meta') && !Schema::hasColumn('audit_logs', 'metadata')) {
            Schema::table('audit_logs', function (Blueprint $table) use ($driver) {
                if ($driver === 'mysql') {
                    DB::statement('ALTER TABLE audit_logs DROP CHECK `audit_logs_chk_1`');
                    DB::statement('ALTER TABLE audit_logs CHANGE `meta` `metadata` JSON NULL');
                    DB::statement('ALTER TABLE audit_logs ADD CONSTRAINT `audit_logs_chk_1` CHECK (json_valid(`metadata`))');
                } else {
                    $table->renameColumn('meta', 'metadata');
                }
            });
        }

        // 2. conversation_participants: rename role -> role_at_join
        if (Schema::hasColumn('conversation_participants', 'role') && !Schema::hasColumn('conversation_participants', 'role_at_join')) {
            Schema::table('conversation_participants', function (Blueprint $table) use ($driver) {
                if ($driver === 'mysql') {
                    DB::statement('ALTER TABLE conversation_participants CHANGE `role` `role_at_join` VARCHAR(50) NULL');
                } else {
                    $table->renameColumn('role', 'role_at_join');
                }
            });
        }

        // 2b. conversation_participants: replace last_read_message_id (bigint) with last_read_at (timestamp)
        if (Schema::hasColumn('conversation_participants', 'last_read_message_id') && !Schema::hasColumn('conversation_participants', 'last_read_at')) {
            Schema::table('conversation_participants', function (Blueprint $table) {
                $table->dropColumn('last_read_message_id');
                $table->timestamp('last_read_at')->nullable()->after('role_at_join');
            });
        }

        // 3. job_applications: add resume_document_id, cover_letter_document_id to model fillable (columns already exist in DB, just ensure model awareness — no migration needed for existing cols)
        // These columns already exist in DB per our check, so nothing to do here.

        // 4. zoom_filter_settings: add privacy_filtering
        if (!Schema::hasColumn('zoom_filter_settings', 'privacy_filtering')) {
            Schema::table('zoom_filter_settings', function (Blueprint $table) {
                $table->boolean('privacy_filtering')->default(true)->after('replacement_text');
            });
        }

        // 5. plans: add stripe_price_id if missing
        if (!Schema::hasColumn('plans', 'stripe_price_id')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->string('stripe_price_id', 255)->nullable()->after('name');
            });
        }

        // 5b. plans: add features_json if missing
        if (!Schema::hasColumn('plans', 'features_json')) {
            Schema::table('plans', function (Blueprint $table) {
                $table->json('features_json')->nullable()->after('is_active');
            });
        }

        // 6. interviews: ensure all model-expected columns exist
        Schema::table('interviews', function (Blueprint $table) {
            if (!Schema::hasColumn('interviews', 'scheduled_start')) {
                $table->timestamp('scheduled_start')->nullable();
            }
            if (!Schema::hasColumn('interviews', 'scheduled_end')) {
                $table->timestamp('scheduled_end')->nullable();
            }
            if (!Schema::hasColumn('interviews', 'mode')) {
                $table->enum('mode', ['in_person', 'video', 'phone'])->nullable();
            }
            if (!Schema::hasColumn('interviews', 'meeting_link')) {
                $table->string('meeting_link', 500)->nullable();
            }
            if (!Schema::hasColumn('interviews', 'location_text')) {
                $table->string('location_text', 255)->nullable();
            }
            if (!Schema::hasColumn('interviews', 'cancel_reason')) {
                $table->text('cancel_reason')->nullable();
            }
            if (!Schema::hasColumn('interviews', 'created_by_user_id')) {
                $table->unsignedBigInteger('created_by_user_id')->nullable();
            }
            if (!Schema::hasColumn('interviews', 'status')) {
                $table->enum('status', ['proposed', 'confirmed', 'rescheduled', 'cancelled', 'completed'])->default('proposed');
            }
        });

        // 7. verification_requests: ensure role, notes, reviewed_by_user_id exist
        Schema::table('verification_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('verification_requests', 'role')) {
                $table->string('role', 30)->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('verification_requests', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (!Schema::hasColumn('verification_requests', 'reviewed_by_user_id')) {
                $table->unsignedBigInteger('reviewed_by_user_id')->nullable();
            }
        });

        // 8. ai_screenings: ensure all model-expected columns exist
        Schema::table('ai_screenings', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_screenings', 'job_id')) {
                $table->unsignedBigInteger('job_id')->nullable()->after('application_id');
            }
            if (!Schema::hasColumn('ai_screenings', 'applicant_user_id')) {
                $table->unsignedBigInteger('applicant_user_id')->nullable()->after('job_id');
            }
            if (!Schema::hasColumn('ai_screenings', 'model_name')) {
                $table->string('model_name', 120)->nullable()->after('applicant_user_id');
            }
            if (!Schema::hasColumn('ai_screenings', 'score')) {
                $table->decimal('score', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('ai_screenings', 'suggestions')) {
                $table->json('suggestions')->nullable();
            }
        });

        // 9. conversations: ensure created_by_user_id exists
        if (!Schema::hasColumn('conversations', 'created_by_user_id')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->unsignedBigInteger('created_by_user_id')->nullable()->after('id');
            });
        }

        // 10. agency_profiles: ensure all model fields exist
        Schema::table('agency_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('agency_profiles', 'verification_status')) {
                $table->string('verification_status', 30)->default('pending');
            }
            if (!Schema::hasColumn('agency_profiles', 'verified_at')) {
                $table->timestamp('verified_at')->nullable();
            }
            if (!Schema::hasColumn('agency_profiles', 'rejected_reason')) {
                $table->text('rejected_reason')->nullable();
            }
            if (!Schema::hasColumn('agency_profiles', 'address_line')) {
                $table->string('address_line', 255)->nullable();
            }
        });
    }

    public function down(): void
    {
        // Reverse rename metadata -> meta
        if (Schema::hasColumn('audit_logs', 'metadata') && !Schema::hasColumn('audit_logs', 'meta')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->renameColumn('metadata', 'meta');
            });
        }

        // Reverse rename role_at_join -> role
        if (Schema::hasColumn('conversation_participants', 'role_at_join')) {
            Schema::table('conversation_participants', function (Blueprint $table) {
                $table->renameColumn('role_at_join', 'role');
            });
        }

        // Drop last_read_at, restore last_read_message_id
        if (Schema::hasColumn('conversation_participants', 'last_read_at')) {
            Schema::table('conversation_participants', function (Blueprint $table) {
                $table->dropColumn('last_read_at');
                $table->unsignedBigInteger('last_read_message_id')->nullable();
            });
        }

        // Drop privacy_filtering
        if (Schema::hasColumn('zoom_filter_settings', 'privacy_filtering')) {
            Schema::table('zoom_filter_settings', function (Blueprint $table) {
                $table->dropColumn('privacy_filtering');
            });
        }
    }
};

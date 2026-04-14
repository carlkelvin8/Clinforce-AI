<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Create endorsements table
     * 
     * Peer endorsement system:
     * - Colleagues can endorse skills
     * - Recommendations and testimonials
     * - Verified endorsements (from confirmed colleagues)
     * - Skill-specific endorsements
     */
    public function up(): void
    {
        if (!Schema::hasTable('endorsements')) {
            Schema::create('endorsements', function (Blueprint $table) {
            $table->id();
            
            // Who is endorsing whom
            $table->foreignId('recipient_user_id')->constrained('users')->onDelete('cascade')
                  ->comment('User receiving the endorsement');
            $table->foreignId('endorser_user_id')->constrained('users')->onDelete('cascade')
                  ->comment('User giving the endorsement');
            
            // Endorsement Type
            $table->enum('type', ['skill', 'recommendation', 'character', 'work_ethic', 'leadership'])
                  ->default('skill')->index();
            $table->string('skill_name')->nullable()
                  ->comment('Specific skill being endorsed (for type=skill)');
            
            // Content
            $table->text('message')->nullable()->comment('Endorsement text');
            $table->integer('rating')->nullable()->comment('1-5 star rating');
            
            // Relationship Context
            $table->string('relationship')->nullable()
                  ->comment('e.g., "Worked together", "Manager", "Colleague", "Client"');
            $table->string('company_name')->nullable()
                  ->comment('Company where they worked together');
            $table->date('start_date')->nullable()->comment('When they started working together');
            $table->date('end_date')->nullable()->comment('When they stopped working together');
            
            // Verification
            $table->boolean('is_verified')->default(false)
                  ->comment('Verified through work history match');
            $table->timestamp('verified_at')->nullable();
            $table->boolean('is_hidden')->default(false)
                  ->comment('Hidden by recipient');
            
            // Engagement
            $table->integer('helpful_count')->default(0)
                  ->comment('Users who found this helpful');
            $table->json('endorsed_by_employers')->nullable()
                  ->comment('Employer user IDs who endorsed (for weighting)');
            
            $table->timestamps();
            
            // Indexes
            $table->unique(['recipient_user_id', 'endorser_user_id', 'skill_name'], 'unique_endorsement')
                  ->comment('One endorsement per skill per relationship');
            $table->index(['recipient_user_id', 'type']);
            $table->index(['recipient_user_id', 'is_hidden']);
            $table->index('skill_name');
        });
        }

        // Endorsement votes (helpful/not helpful)
        if (!Schema::hasTable('endorsement_votes')) {
            Schema::create('endorsement_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('endorsement_id')->constrained('endorsements')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_helpful')->comment('true = helpful, false = not helpful');
            
            $table->timestamps();
            
            $table->unique(['endorsement_id', 'user_id']);
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('endorsement_votes');
        Schema::dropIfExists('endorsements');
    }
};

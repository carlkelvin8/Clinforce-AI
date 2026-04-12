<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Create portfolios table
     * 
     * Portfolio items for showcasing work:
     * - Images (designs, infographics, presentations)
     * - Videos (demos, tutorials, talks)
     * - Links (GitHub, Behance, Dribbble, articles)
     * - Documents (case studies, whitepapers)
     */
    public function up(): void
    {
        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Item Details
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['image', 'video', 'link', 'document', 'project'])->index();
            
            // Media Storage
            $table->string('media_url')->nullable()->comment('URL to uploaded file or external media');
            $table->string('thumbnail_url')->nullable()->comment('Thumbnail for preview');
            $table->string('embed_url')->nullable()->comment('Embed URL for YouTube, Vimeo, etc.');
            
            // External Links
            $table->string('external_url')->nullable()->comment('Link to GitHub, article, etc.');
            $table->string('domain')->nullable()->comment('Extracted domain for display');
            
            // Categorization
            $table->string('category')->nullable()->index()->comment('e.g., "UI Design", "Research", "Code"');
            $table->json('tags')->nullable()->comment('Searchable tags');
            
            // Project-specific fields
            $table->json('project_details')->nullable()->comment('Tech stack, role, duration, outcomes');
            $table->date('completed_at')->nullable()->comment('When project was completed');
            
            // Visibility & Ordering
            $table->boolean('is_public')->default(true)->index();
            $table->boolean('is_featured')->default(false)->index()->comment('Show in featured section');
            $table->integer('display_order')->default(0)->comment('Custom ordering');
            
            // Engagement
            $table->integer('views')->default(0);
            $table->json('attachments')->nullable()->comment('Additional files/links');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'is_public']);
            $table->index(['user_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portfolios');
    }
};

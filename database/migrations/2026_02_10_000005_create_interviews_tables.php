<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One interview per application (re-scheduling updates the same row)
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->unique()->constrained('applications')->cascadeOnDelete();
            $table->foreignId('applicant_id')->nullable()->constrained('applicants')->cascadeOnDelete();
            $table->foreignId('vacancy_id')->nullable()->constrained('vacancies')->nullOnDelete();

            $table->timestamp('scheduled_at')->nullable()->index();
            $table->string('mode', 16)->default('unspecified');
            $table->string('location', 500)->nullable();
            $table->string('meeting_link', 500)->nullable();
            $table->text('extra_info')->nullable();

            // HR-side lifecycle and the candidate's response
            $table->string('status', 16)->default('invited')->index();
            $table->string('candidate_status', 20)->default('pending');
            $table->text('candidate_reason')->nullable();

            // Panel evaluation results (0-100)
            $table->unsignedSmallInteger('panel_count')->default(0);
            $table->unsignedSmallInteger('interview_score')->nullable();
            $table->unsignedSmallInteger('final_score')->nullable();

            $table->timestamp('reminder_3d_sent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('interview_panels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_id')->constrained('interviews')->cascadeOnDelete();
            $table->unsignedSmallInteger('panel_no');
            $table->string('name');
            $table->string('department')->nullable();
            $table->unsignedSmallInteger('rating')->nullable();   // 1-5, null until rated
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['interview_id', 'panel_no']);
        });

        if (DB::getDriverName() !== 'pgsql') return;

        DB::statement("ALTER TABLE interviews ADD CONSTRAINT interviews_mode_check CHECK (mode IN ('onsite', 'online', 'phone', 'hybrid', 'unspecified'))");
        DB::statement("ALTER TABLE interviews ADD CONSTRAINT interviews_status_check CHECK (status IN ('invited', 'scheduled', 'done', 'scored', 'no_show', 'cancelled'))");
        DB::statement("ALTER TABLE interviews ADD CONSTRAINT interviews_candidate_status_check CHECK (candidate_status IN ('pending', 'confirmed', 'declined', 'cancelled_by_hr', 'hired'))");
        DB::statement('ALTER TABLE interviews ADD CONSTRAINT interviews_scores_range CHECK ((interview_score IS NULL OR interview_score <= 100) AND (final_score IS NULL OR final_score <= 100))');
        DB::statement('ALTER TABLE interview_panels ADD CONSTRAINT interview_panels_rating_range CHECK (rating IS NULL OR rating BETWEEN 1 AND 5)');
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_panels');
        Schema::dropIfExists('interviews');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('applicants')->cascadeOnDelete();
            $table->foreignId('vacancy_id')->constrained('vacancies')->cascadeOnDelete();
            $table->foreignId('resume_id')->nullable()->constrained('resumes')->nullOnDelete();
            $table->string('status', 20)->default('submitted');

            // AI match result (0-100, null while being re-scored) and the per-criterion breakdown
            $table->decimal('match_score', 5, 2)->nullable()->default(0);
            $table->jsonb('match_breakdown')->nullable();

            $table->timestamps();

            // An applicant applies to a vacancy once; withdrawing and re-applying reuses the row
            $table->unique(['applicant_id', 'vacancy_id']);
            $table->index(['vacancy_id', 'status']);
            $table->index(['vacancy_id', 'match_score']);
        });

        // Timeline of every status change, written by a database trigger so no
        // code path (controller, job, manual SQL) can skip it.
        Schema::create('application_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['application_id', 'created_at']);
        });

        if (DB::getDriverName() !== 'pgsql') return;

        DB::statement("ALTER TABLE applications ADD CONSTRAINT applications_status_check CHECK (status IN ('submitted', 'in_review', 'shortlisted', 'rejected', 'hired', 'withdrawn'))");
        DB::statement('ALTER TABLE applications ADD CONSTRAINT applications_match_score_range CHECK (match_score IS NULL OR match_score BETWEEN 0 AND 100)');

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION log_application_status() RETURNS trigger AS $$
            BEGIN
                IF TG_OP = 'INSERT' OR NEW.status IS DISTINCT FROM OLD.status THEN
                    INSERT INTO application_status_histories (application_id, from_status, to_status, changed_by, created_at)
                    VALUES (
                        NEW.id,
                        CASE WHEN TG_OP = 'UPDATE' THEN OLD.status END,
                        NEW.status,
                        NULLIF(current_setting('app.user_id', true), '')::bigint,
                        now()
                    );
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER applications_status_history
                AFTER INSERT OR UPDATE OF status ON applications
                FOR EACH ROW EXECUTE FUNCTION log_application_status();
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS applications_status_history ON applications; DROP FUNCTION IF EXISTS log_application_status();');
        }
        Schema::dropIfExists('application_status_histories');
        Schema::dropIfExists('applications');
    }
};

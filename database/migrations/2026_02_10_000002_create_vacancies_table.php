<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacancies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('department', 120)->nullable()->index();
            $table->string('location')->nullable();
            $table->string('employment_type', 20)->default('permanent');
            $table->text('description')->nullable();
            $table->date('closing_date')->nullable()->index();
            $table->string('status', 20)->default('Open')->index();

            // Screening criteria used by the AI match scorer
            $table->jsonb('skills_required')->nullable();
            $table->unsignedSmallInteger('experience_min_years')->nullable();
            $table->unsignedSmallInteger('experience_max_years')->nullable();
            $table->string('education_required', 200)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('department')->references('name')->on('departments')
                ->cascadeOnUpdate()->restrictOnDelete();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE vacancies ADD CONSTRAINT vacancies_status_check CHECK (status IN ('Open', 'Closed', 'Archived'))");
            DB::statement("ALTER TABLE vacancies ADD CONSTRAINT vacancies_employment_type_check CHECK (employment_type IN ('permanent', 'contract', 'intern'))");
            DB::statement("ALTER TABLE vacancies ADD CONSTRAINT vacancies_experience_range_check CHECK (experience_min_years IS NULL OR experience_max_years IS NULL OR experience_min_years <= experience_max_years)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vacancies');
    }
};

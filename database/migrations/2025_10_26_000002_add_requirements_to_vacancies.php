<?php

// database/migrations/2025_10_26_000002_add_requirements_to_vacancies.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('vacancies', function (Blueprint $t) {
            if (!Schema::hasColumn('vacancies','requirements')) {
                $t->jsonb('requirements')->nullable(); 
                // Example:
                // {
                //   "skills_required": ["javascript","vue","postgresql"],
                //   "min_experience_years": 2,
                //   "min_education_level": "bachelor"
                // }
            }
        });
    }
    public function down(): void {
        Schema::table('vacancies', function (Blueprint $t) {
            $t->dropColumn('requirements');
        });
    }
};

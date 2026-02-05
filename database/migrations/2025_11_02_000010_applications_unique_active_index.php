<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // One active application per (applicant_id, vacancy_id)
        DB::statement("
            CREATE UNIQUE INDEX IF NOT EXISTS applications_unique_active
            ON applications(applicant_id, vacancy_id)
            WHERE status IN ('submitted','in_review','shortlisted')
        ");
    }

    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS applications_unique_active");
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('resumes', 'application_id')) {
            // Postgres: drop NOT NULL without DBAL
            DB::statement('ALTER TABLE resumes ALTER COLUMN application_id DROP NOT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('resumes', 'application_id')) {
            DB::statement('ALTER TABLE resumes ALTER COLUMN application_id SET NOT NULL');
        }
    }
};

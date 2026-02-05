<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('resumes') && Schema::hasColumn('resumes', 'application_id')) {
            // Postgres-friendly way to drop NOT NULL without requiring doctrine/dbal
            DB::statement('ALTER TABLE resumes ALTER COLUMN application_id DROP NOT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('resumes') && Schema::hasColumn('resumes', 'application_id')) {
            // Caution: will fail if existing nulls are present
            DB::statement('ALTER TABLE resumes ALTER COLUMN application_id SET NOT NULL');
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('interviews')) {
            // Allow NULL first
            DB::statement("ALTER TABLE interviews ALTER COLUMN mode DROP NOT NULL");
            // Backfill any NULLs (safety)
            DB::statement("UPDATE interviews SET mode = 'unspecified' WHERE mode IS NULL");
            // New default going forward
            DB::statement("ALTER TABLE interviews ALTER COLUMN mode SET DEFAULT 'unspecified'");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('interviews')) {
            DB::statement("ALTER TABLE interviews ALTER COLUMN mode DROP DEFAULT");
            DB::statement("ALTER TABLE interviews ALTER COLUMN mode SET NOT NULL");
        }
    }
};

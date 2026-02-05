<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('interviews')) return;

        // 1) Drop existing CHECK (if any)
        DB::statement("ALTER TABLE interviews DROP CONSTRAINT IF EXISTS interviews_mode_check");

        // 2) Make 'mode' nullable (in case it was NOT NULL before)
        DB::statement("ALTER TABLE interviews ALTER COLUMN mode DROP NOT NULL");

        // 3) Add a permissive CHECK that includes 'unspecified'
        //    Edit the list below if your project uses different labels.
        DB::statement("
            ALTER TABLE interviews
            ADD CONSTRAINT interviews_mode_check
            CHECK (
                mode IS NULL OR mode IN ('onsite','online','phone','hybrid','unspecified')
            )
        ");

        // 4) (Optional) Default to 'unspecified' on new rows
        DB::statement("ALTER TABLE interviews ALTER COLUMN mode SET DEFAULT 'unspecified'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('interviews')) return;

        // Drop our relaxed CHECK
        DB::statement("ALTER TABLE interviews DROP CONSTRAINT IF EXISTS interviews_mode_check");

        // Remove default; leave column nullable to be safe
        DB::statement("ALTER TABLE interviews ALTER COLUMN mode DROP DEFAULT");

        // If you need to restore your original strict CHECK, re-add it here, e.g.:
        // DB::statement(\"ALTER TABLE interviews ADD CONSTRAINT interviews_mode_check CHECK (mode IN ('onsite','online','phone','hybrid'))\");
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('interviews')) return;

        // Drop existing CHECK (name may differ, keep IF EXISTS)
        DB::statement("ALTER TABLE interviews DROP CONSTRAINT IF EXISTS interviews_status_check");

        // Expand allowed statuses to include 'invited'
        DB::statement("
            ALTER TABLE interviews
            ADD CONSTRAINT interviews_status_check
            CHECK (
                status IN (
                    'invited',        -- new: when HR just sent invite
                    'scheduled',      -- time fixed
                    'in_progress',    -- ongoing
                    'scored',         -- panels scored
                    'finalized',      -- final score computed
                    'cancelled',
                    'no_show'
                )
            )
        ");

        // Optional: default to 'invited' on create
        DB::statement("ALTER TABLE interviews ALTER COLUMN status SET DEFAULT 'invited'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('interviews')) return;

        DB::statement("ALTER TABLE interviews DROP CONSTRAINT IF EXISTS interviews_status_check");
        DB::statement("ALTER TABLE interviews ALTER COLUMN status DROP DEFAULT");

        // (Optional) Re-create your previous strict check here if you want to restore it.
        // DB::statement(\"ALTER TABLE interviews ADD CONSTRAINT interviews_status_check CHECK (status IN ('scheduled','finalized'))\");
    }
};

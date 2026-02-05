<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add panel_no if missing
        if (!Schema::hasColumn('interview_panels', 'panel_no')) {
            Schema::table('interview_panels', function (Blueprint $t) {
                $t->integer('panel_no')->nullable(); // fill first, then NOT NULL
            });

            // Backfill sequential panel_no per interview (1..N by id order)
            DB::statement("
                WITH ranked AS (
                  SELECT id, interview_id,
                         ROW_NUMBER() OVER (PARTITION BY interview_id ORDER BY id) AS rn
                  FROM interview_panels
                )
                UPDATE interview_panels p
                   SET panel_no = r.rn
                  FROM ranked r
                 WHERE p.id = r.id AND p.panel_no IS NULL
            ");

            // Enforce NOT NULL (raw SQL avoids doctrine/dbal dependency)
            DB::statement('ALTER TABLE interview_panels ALTER COLUMN panel_no SET NOT NULL');
        }

        // Ensure rating exists (in case your original table lacked it)
        if (!Schema::hasColumn('interview_panels', 'rating')) {
            Schema::table('interview_panels', function (Blueprint $t) {
                $t->smallInteger('rating')->nullable();
            });
        }

        // Unique (interview_id, panel_no) to make 1..N panels per interview
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS interview_panels_interview_id_panel_no_unique ON interview_panels (interview_id, panel_no)');
    }

    public function down(): void
    {
        // Drop unique index + column (safe on Postgres)
        DB::statement('DROP INDEX IF EXISTS interview_panels_interview_id_panel_no_unique');

        if (Schema::hasColumn('interview_panels', 'panel_no')) {
            Schema::table('interview_panels', function (Blueprint $t) {
                $t->dropColumn('panel_no');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // allow NULL and set a default for new rows
        DB::statement("ALTER TABLE applications ALTER COLUMN match_score DROP NOT NULL");
        DB::statement("ALTER TABLE applications ALTER COLUMN match_score SET DEFAULT 0");

        // normalise any existing NULLs to 0 (optional but nice)
        DB::statement("UPDATE applications SET match_score = 0 WHERE match_score IS NULL");
    }

    public function down(): void
    {
        // make sure no NULLs exist before re-enforcing NOT NULL
        DB::statement("UPDATE applications SET match_score = 0 WHERE match_score IS NULL");

        DB::statement("ALTER TABLE applications ALTER COLUMN match_score DROP DEFAULT");
        DB::statement("ALTER TABLE applications ALTER COLUMN match_score SET NOT NULL");
    }
};

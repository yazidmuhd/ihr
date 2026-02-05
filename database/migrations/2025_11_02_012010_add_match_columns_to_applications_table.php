<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (!Schema::hasColumn('applications', 'match_score')) {
                // small int is enough for 0-100; use integer() if you prefer
                $table->unsignedSmallInteger('match_score')->nullable();
            }

            if (!Schema::hasColumn('applications', 'match_breakdown')) {
                // Laravel maps ->json() to JSON on Postgres/MySQL.
                // We'll convert to JSONB right after for Postgres.
                $table->json('match_breakdown')->nullable();
            }
        });

        // If you're on Postgres, convert JSON → JSONB (optional but nice)
        if (config('database.default') === 'pgsql') {
            $row = DB::selectOne("
                SELECT udt_name
                FROM information_schema.columns
                WHERE table_name = 'applications' AND column_name = 'match_breakdown'
                LIMIT 1
            ");
            if ($row && ($row->udt_name ?? null) !== 'jsonb') {
                DB::statement("
                    ALTER TABLE applications
                    ALTER COLUMN match_breakdown
                    TYPE jsonb
                    USING match_breakdown::jsonb
                ");
            }
        }
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'match_breakdown')) {
                $table->dropColumn('match_breakdown');
            }
            if (Schema::hasColumn('applications', 'match_score')) {
                $table->dropColumn('match_score');
            }
        });
    }
};

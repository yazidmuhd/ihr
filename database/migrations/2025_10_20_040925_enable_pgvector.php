<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Don't wrap in a single transaction so a failed statement doesn't poison the run
    public $withinTransaction = false;

    public function up(): void
    {
        // If not Postgres, or extension unavailable, just skip silently.
        if (DB::getDriverName() !== 'pgsql') return;

        try {
            // Only attempt if the extension is available on the server
            $available = DB::selectOne("SELECT 1 FROM pg_available_extensions WHERE name = 'vector'");
            if ($available) {
                DB::statement("CREATE EXTENSION IF NOT EXISTS vector");
            }
        } catch (\Throwable $e) {
            // Skip gracefully (JSON fallback will be used by other migrations)
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') return;
        try {
            DB::statement("DROP EXTENSION IF EXISTS vector");
        } catch (\Throwable $e) {
            // ignore
        }
    }
};

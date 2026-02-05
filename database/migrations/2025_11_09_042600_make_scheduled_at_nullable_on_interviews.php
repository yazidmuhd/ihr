<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Postgres-safe: drop NOT NULL without requiring doctrine/dbal
        if (Schema::hasTable('interviews')) {
            DB::statement('ALTER TABLE interviews ALTER COLUMN scheduled_at DROP NOT NULL');
        }
    }

    public function down(): void
    {
        // If you want to enforce NOT NULL again (only do this if all rows have a value)
        if (Schema::hasTable('interviews')) {
            DB::statement('ALTER TABLE interviews ALTER COLUMN scheduled_at SET NOT NULL');
        }
    }
};

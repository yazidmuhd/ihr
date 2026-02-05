<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop existing check constraint (if any)
        DB::statement("ALTER TABLE interviews DROP CONSTRAINT IF EXISTS interviews_status_check");

        // Add updated allowed statuses
        DB::statement("
            ALTER TABLE interviews
            ADD CONSTRAINT interviews_status_check
            CHECK (status IN ('invited','scheduled','done','scored','no_show','cancelled'))
        ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE interviews DROP CONSTRAINT IF EXISTS interviews_status_check");

        // rollback version (remove done)
        DB::statement("
            ALTER TABLE interviews
            ADD CONSTRAINT interviews_status_check
            CHECK (status IN ('invited','scheduled','scored','no_show','cancelled'))
        ");
    }
};

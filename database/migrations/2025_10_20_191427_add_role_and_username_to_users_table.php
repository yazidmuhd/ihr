<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only add columns if they don't already exist
        if (! Schema::hasColumn('users', 'username') || ! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'username')) {
                    $table->string('username')->nullable()->after('email');
                }

                if (! Schema::hasColumn('users', 'role')) {
                    // adjust default if you use something else (e.g. 'hr', 'applicant')
                    $table->string('role')->default('applicant')->after('username');
                }
            });
        }

        // If you want a unique username, add the index only if it’s missing.
        // Laravel doesn't have hasIndex(), so skip if you already created it manually.
        // Example (safe to rerun in Postgres using IF NOT EXISTS):
        try {
            Schema::table('users', function (Blueprint $table) {
                // This try/catch keeps it from crashing if the index exists already.
                $table->unique('username', 'users_username_unique');
            });
        } catch (\Throwable $e) {
            // ignore "already exists" errors for the unique index
        }
    }

    public function down(): void
    {
        // Drop only if present
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'username')) {
                // drop unique first if it exists
                try { $table->dropUnique('users_username_unique'); } catch (\Throwable $e) {}
                $table->dropColumn('username');
            }

            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};

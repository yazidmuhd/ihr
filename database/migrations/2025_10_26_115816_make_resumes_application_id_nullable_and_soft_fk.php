<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Ensure the column exists (nullable)
        if (! Schema::hasColumn('resumes', 'application_id')) {
            Schema::table('resumes', function (Blueprint $table) {
                // If your applications.id is BIGINT (Laravel default), use unsignedBigInteger/bigInteger
                $table->unsignedBigInteger('application_id')->nullable()->after('id');
                $table->index('application_id', 'resumes_application_id_index');
            });
        }

        // 2) If you previously used a different name (e.g., job_application_id), copy values across
        if (Schema::hasColumn('resumes', 'job_application_id') && Schema::hasColumn('resumes', 'application_id')) {
            DB::statement('UPDATE resumes SET application_id = job_application_id WHERE application_id IS NULL');
            // (Optional) drop the old column later if you want, but keep it for now to avoid requiring DBAL for renames
        }

        // 3) Add FK only if applications table exists and column is present
        if (Schema::hasTable('applications') && Schema::hasColumn('resumes', 'application_id')) {
            try {
                Schema::table('resumes', function (Blueprint $table) {
                    $table->foreign('application_id', 'resumes_application_id_foreign')
                        ->references('id')->on('applications')
                        ->nullOnDelete(); // ON DELETE SET NULL
                });
            } catch (\Throwable $e) {
                // ignore if FK already exists or type mismatch; we just want the migration to be idempotent
            }
        }
    }

    public function down(): void
    {
        // Drop FK if present
        try {
            Schema::table('resumes', function (Blueprint $table) {
                $table->dropForeign('resumes_application_id_foreign');
            });
        } catch (\Throwable $e) {
            // ignore
        }

        // Drop index + column if present
        Schema::table('resumes', function (Blueprint $table) {
            if (Schema::hasColumn('resumes', 'application_id')) {
                try { $table->dropIndex('resumes_application_id_index'); } catch (\Throwable $e) {}
                $table->dropColumn('application_id');
            }
        });
    }
};

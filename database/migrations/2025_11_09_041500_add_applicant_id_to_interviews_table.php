<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            if (! Schema::hasColumn('interviews', 'applicant_id')) {
                // nullable is fine; we can backfill later if needed
                $table->unsignedBigInteger('applicant_id')->nullable()->after('application_id');
                $table->index('applicant_id', 'interviews_applicant_id_idx');
                // Optional FK (only if you have an applicants/users table to reference)
                // $table->foreign('applicant_id')->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            if (Schema::hasColumn('interviews', 'applicant_id')) {
                // If you created a FK above, drop it first:
                // $table->dropForeign(['applicant_id']);
                $table->dropIndex('interviews_applicant_id_idx');
                $table->dropColumn('applicant_id');
            }
        });
    }
};

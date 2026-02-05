<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
    if (!Schema::hasColumn('applications','vacancy_id') || !Schema::hasColumn('applications','applicant_id')) return;
    $table->unique(['vacancy_id','applicant_id'], 'applications_vacancy_applicant_unique');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
    $table->dropUnique('applications_vacancy_applicant_unique');
});
    }
};

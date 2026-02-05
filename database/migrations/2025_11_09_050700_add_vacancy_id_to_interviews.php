<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('interviews') && !Schema::hasColumn('interviews', 'vacancy_id')) {
            Schema::table('interviews', function (Blueprint $table) {
                // nullable so invite-without-schedule works; link to vacancies
                $table->foreignId('vacancy_id')
                      ->nullable()
                      ->constrained('vacancies')
                      ->nullOnDelete(); // or ->cascadeOnDelete() if you prefer
            });

            // Backfill vacancy_id using the related application
            DB::statement("
                UPDATE interviews i
                SET vacancy_id = a.vacancy_id
                FROM applications a
                WHERE a.id = i.application_id
                  AND i.vacancy_id IS NULL
            ");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('interviews') && Schema::hasColumn('interviews', 'vacancy_id')) {
            Schema::table('interviews', function (Blueprint $table) {
                // Drops FK and column in one go
                $table->dropConstrainedForeignId('vacancy_id');
            });
        }
    }
};

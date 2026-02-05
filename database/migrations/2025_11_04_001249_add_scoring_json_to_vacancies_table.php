<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            // PostgreSQL: use jsonb
            $table->jsonb('skills_required')->nullable();
            $table->jsonb('scoring_config')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropColumn(['skills_required', 'scoring_config']);
        });
    }
};


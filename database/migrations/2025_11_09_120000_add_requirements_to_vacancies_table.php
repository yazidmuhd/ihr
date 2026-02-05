<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            // keep nullable to avoid breaking existing rows; enforce via validation in controller
            $table->integer('experience_years_required')->nullable()->after('location');
            $table->string('education_required', 32)->nullable()->after('experience_years_required');
        });
    }

    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropColumn(['experience_years_required', 'education_required']);
        });
    }
};

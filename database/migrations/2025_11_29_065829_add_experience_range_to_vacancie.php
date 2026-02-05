<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_experience_range_to_vacancies.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->unsignedInteger('experience_min_years')->nullable()->after('experience_years_required');
            $table->unsignedInteger('experience_max_years')->nullable()->after('experience_min_years');
        });
    }

    public function down(): void
    {
        Schema::table('vacancies', function (Blueprint $table) {
            $table->dropColumn(['experience_min_years', 'experience_max_years']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(): void
    {
        // If column exists, widen it to 200 chars (or switch to TEXT if you prefer).
        if (Schema::hasColumn('vacancies', 'education_required')) {
            // Postgres-friendly raw SQL (no need for doctrine/dbal)
            DB::statement("ALTER TABLE vacancies ALTER COLUMN education_required TYPE varchar(200)");
        } else {
            Schema::table('vacancies', function (Blueprint $table) {
                $table->string('education_required', 200)->nullable();
            });
        }
    }

    public function down(): void
    {
        // If you want a reversible down, you’ll need doctrine/dbal for ->change().
        // Quick raw SQL rollback to 32 if you really need it:
        // DB::statement(\"ALTER TABLE vacancies ALTER COLUMN education_required TYPE varchar(32)\");
    }
};

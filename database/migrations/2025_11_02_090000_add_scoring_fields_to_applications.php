<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (!Schema::hasColumn('applications', 'status')) {
                $table->string('status')->default('submitted')->index();
            }
            if (!Schema::hasColumn('applications', 'match_score')) {
                $table->integer('match_score')->nullable()->index();
            }
            if (!Schema::hasColumn('applications', 'match_breakdown')) {
                // json works on PG/MySQL; PG uses jsonb automatically via doctrine/dbal if installed
                $table->json('match_breakdown')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'match_breakdown')) {
                $table->dropColumn('match_breakdown');
            }
            if (Schema::hasColumn('applications', 'match_score')) {
                $table->dropColumn('match_score');
            }
            if (Schema::hasColumn('applications', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};

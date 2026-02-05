<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('applications', function (Blueprint $table) {
            if (!Schema::hasColumn('applications', 'resume_id')) {
                $table->unsignedBigInteger('resume_id')->nullable()->after('applicant_id');
            }
            if (!Schema::hasColumn('applications', 'match_score')) {
                $table->unsignedSmallInteger('match_score')->default(0)->after('resume_id');
            }
            if (!Schema::hasColumn('applications', 'match_breakdown')) {
                $table->json('match_breakdown')->nullable()->after('match_score');
            }
            if (!Schema::hasColumn('applications', 'ai_summary')) {
                $table->text('ai_summary')->nullable()->after('match_breakdown');
            }
        });
    }

    public function down(): void {
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'ai_summary')) $table->dropColumn('ai_summary');
            if (Schema::hasColumn('applications', 'match_breakdown')) $table->dropColumn('match_breakdown');
            if (Schema::hasColumn('applications', 'match_score')) $table->dropColumn('match_score');
            if (Schema::hasColumn('applications', 'resume_id')) $table->dropColumn('resume_id');
        });
    }
};

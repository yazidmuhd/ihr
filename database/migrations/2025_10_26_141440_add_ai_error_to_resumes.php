<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            if (!Schema::hasColumn('resumes', 'ai_status')) {
                $table->string('ai_status')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('resumes', 'ai_parsed')) {
                $table->longText('ai_parsed')->nullable()->after('ai_status');
            }
            if (!Schema::hasColumn('resumes', 'ai_error')) {
                $table->text('ai_error')->nullable()->after('ai_parsed');
            }
        });
    }

    public function down(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            if (Schema::hasColumn('resumes', 'ai_error'))  $table->dropColumn('ai_error');
            if (Schema::hasColumn('resumes', 'ai_parsed')) $table->dropColumn('ai_parsed');
            if (Schema::hasColumn('resumes', 'ai_status')) $table->dropColumn('ai_status');
        });
    }
};

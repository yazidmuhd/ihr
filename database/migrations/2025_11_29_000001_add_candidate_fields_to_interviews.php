<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            // candidate_status: pending | accepted | declined
            if (!Schema::hasColumn('interviews', 'candidate_status')) {
                $table->string('candidate_status')
                    ->default('pending');
            }

            // reason when declined
            if (!Schema::hasColumn('interviews', 'candidate_reason')) {
                $table->text('candidate_reason')->nullable();
            }

            // meeting_link: for online interviews
            if (!Schema::hasColumn('interviews', 'meeting_link')) {
                $table->string('meeting_link')->nullable();
            }

            // extra_info: misc notes (panel, dress code, remarks, etc.)
            if (!Schema::hasColumn('interviews', 'extra_info')) {
                $table->text('extra_info')->nullable();
            }

            // ⚠️ we are NOT touching `location` or `mode` here,
            // because they already exist in your table.
        });
    }

    public function down(): void
    {
        Schema::table('interviews', function (Blueprint $table) {
            if (Schema::hasColumn('interviews', 'candidate_status')) {
                $table->dropColumn('candidate_status');
            }
            if (Schema::hasColumn('interviews', 'candidate_reason')) {
                $table->dropColumn('candidate_reason');
            }
            if (Schema::hasColumn('interviews', 'meeting_link')) {
                $table->dropColumn('meeting_link');
            }
            if (Schema::hasColumn('interviews', 'extra_info')) {
                $table->dropColumn('extra_info');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $table->foreignId('user_id')
                  ->nullable()
                  ->unique()
                  ->constrained('users')
                  ->onDelete('cascade');

            // optional: align column names if your table uses full_name
            if (Schema::hasColumn('applicants', 'full_name') && !Schema::hasColumn('applicants', 'name')) {
                $table->renameColumn('full_name', 'name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            if (Schema::hasColumn('applicants', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropUnique(['user_id']);
                $table->dropColumn('user_id');
            }
            // revert rename only if you actually renamed it above
            // if (Schema::hasColumn('applicants', 'name')) {
            //     $table->renameColumn('name', 'full_name');
            // }
        });
    }
};

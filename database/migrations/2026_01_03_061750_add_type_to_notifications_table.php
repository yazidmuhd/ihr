<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        // If column already exists, do nothing
        if (Schema::hasColumn('notifications', 'type')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            $table->string('type')->nullable();
        });
    }

    public function down(): void
    {
         if (!Schema::hasTable('notifications')) {
            return;
        }

        if (!Schema::hasColumn('notifications', 'type')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};

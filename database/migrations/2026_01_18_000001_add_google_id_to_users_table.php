<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // No-op because google_id already added by 2026_01_18_000001 migration
        if (!Schema::hasTable('users') || Schema::hasColumn('users', 'google_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable();
        });
    }

    public function down(): void
    {
        // Optional: do nothing (since earlier migration owns this column)
        return;
    }
};




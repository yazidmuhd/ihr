<?php

// database/migrations/2026_01_05_000001_add_sort_order_to_resumes_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->index();
        });
    }

    public function down(): void
    {
        Schema::table('resumes', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};

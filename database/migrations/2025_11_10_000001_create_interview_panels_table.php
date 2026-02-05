<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // If table already exists, optionally patch missing columns then exit.
        if (Schema::hasTable('interview_panels')) {
            if (!Schema::hasColumn('interview_panels', 'notes')) {
                Schema::table('interview_panels', function (Blueprint $t) {
                    $t->text('notes')->nullable();
                });
            }
            return; // important: don't recreate / re-FK
        }

        Schema::create('interview_panels', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('interview_id');
            $t->unsignedInteger('panel_no');             // 1..N
            $t->unsignedTinyInteger('rating')->nullable(); // 1..5
            $t->text('notes')->nullable();
            $t->timestamps();

            $t->unique(['interview_id','panel_no'], 'interview_panels_unique');
        });

        // Add FK only if interviews table exists
        Schema::table('interview_panels', function (Blueprint $t) {
            if (Schema::hasTable('interviews')) {
                $t->foreign('interview_id')
                  ->references('id')->on('interviews')
                  ->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('interview_panels')) {
            Schema::drop('interview_panels');
        }
    }
};

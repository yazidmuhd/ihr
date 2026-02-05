<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ---- interviews
        if (!Schema::hasTable('interviews')) {
            Schema::create('interviews', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('vacancy_id');
                $t->unsignedBigInteger('applicant_id');
                $t->unsignedBigInteger('application_id');
                $t->timestamp('scheduled_at')->nullable();
                $t->unsignedSmallInteger('panel_count')->default(0);
                $t->unsignedSmallInteger('interview_score')->nullable(); // 0..100
                $t->unsignedSmallInteger('final_score')->nullable();      // 0..100
                $t->unsignedBigInteger('created_by')->nullable();
                $t->string('status', 32)->default('draft');
                $t->timestamps();

                $t->index(['vacancy_id']);
                $t->index(['application_id']);
            });
        } else {
            Schema::table('interviews', function (Blueprint $t) {
                if (!Schema::hasColumn('interviews','scheduled_at'))   $t->timestamp('scheduled_at')->nullable();
                if (!Schema::hasColumn('interviews','panel_count'))     $t->unsignedSmallInteger('panel_count')->default(0);
                if (!Schema::hasColumn('interviews','interview_score')) $t->unsignedSmallInteger('interview_score')->nullable();
                if (!Schema::hasColumn('interviews','final_score'))     $t->unsignedSmallInteger('final_score')->nullable();
                if (!Schema::hasColumn('interviews','created_by'))      $t->unsignedBigInteger('created_by')->nullable();
                if (!Schema::hasColumn('interviews','status'))          $t->string('status', 32)->default('draft');
            });
        }

        // ---- interview_panels
        if (!Schema::hasTable('interview_panels')) {
            Schema::create('interview_panels', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('interview_id');
                $t->string('name');
                $t->string('department')->nullable();
                $t->unsignedTinyInteger('stars')->default(0); // 0..5
                $t->text('comment')->nullable();
                $t->timestamps();

                $t->index(['interview_id']);
            });
        } else {
            Schema::table('interview_panels', function (Blueprint $t) {
                if (!Schema::hasColumn('interview_panels','department')) $t->string('department')->nullable();
                if (!Schema::hasColumn('interview_panels','stars'))      $t->unsignedTinyInteger('stars')->default(0);
                if (!Schema::hasColumn('interview_panels','comment'))    $t->text('comment')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_panels');
        Schema::dropIfExists('interviews');
    }
};

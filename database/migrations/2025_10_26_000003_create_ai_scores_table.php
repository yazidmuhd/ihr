<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ai_scores', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('applicant_id');
            $t->unsignedBigInteger('resume_id');
            $t->unsignedBigInteger('vacancy_id');
            $t->unsignedSmallInteger('score'); // 0..100
            $t->jsonb('details'); // reasons, overlaps, missing, weights
            $t->timestamps();

            $t->unique(['applicant_id','resume_id','vacancy_id']);
            $t->index(['vacancy_id','score']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('ai_scores');
    }
};

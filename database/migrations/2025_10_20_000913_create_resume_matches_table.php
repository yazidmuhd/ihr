<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('resume_matches', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('vacancy_id')->constrained('vacancies')->cascadeOnDelete();
            $table->integer('total_score');
            $table->jsonb('section_scores');   // {"skill":22,"education":10,"experience":18}
            $table->jsonb('matched_spans')->nullable(); // [{"pattern":"python","start":123,"end":129}, ...]
            $table->unsignedBigInteger('overridden_by')->nullable();
            $table->string('override_note', 500)->nullable();
            $table->timestamps();

            $table->unique(['application_id','vacancy_id'], 'uq_match_pair');
        });
    }
    public function down(): void {
        Schema::dropIfExists('resume_matches');
    }
};

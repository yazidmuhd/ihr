<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vacancy_rules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('vacancy_id')->constrained('vacancies')->cascadeOnDelete();
            $table->enum('rule_type', ['skill','education','experience','cert','softskill']);
            $table->string('pattern', 255);        // keyword/phrase or regex
            $table->integer('weight')->default(1);
            $table->boolean('is_regex')->default(false);
            $table->timestamps();

            $table->unique(['vacancy_id','rule_type','pattern'], 'uq_vac_rule');
        });
    }
    public function down(): void {
        Schema::dropIfExists('vacancy_rules');
    }
};

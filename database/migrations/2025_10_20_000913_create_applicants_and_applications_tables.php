<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('applicants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('full_name', 180)->nullable();
            $table->string('email', 180)->nullable()->index();
            $table->string('phone', 60)->nullable();
            $table->timestamps();
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('applicant_id')->constrained('applicants');
            $table->foreignId('vacancy_id')->constrained('vacancies')->cascadeOnDelete();
            $table->enum('status', ['submitted','under_review','shortlisted','interview','offer','hired','rejected'])
                  ->default('submitted');
            $table->timestamps();

            $table->unique(['applicant_id','vacancy_id'], 'uq_applicant_vacancy');
        });
    }
    public function down(): void {
        Schema::dropIfExists('applications');
        Schema::dropIfExists('applicants');
    }
};

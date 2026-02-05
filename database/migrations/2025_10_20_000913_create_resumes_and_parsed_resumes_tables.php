<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('resumes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->string('filename', 255);
            $table->string('mime_type', 120)->nullable();
            $table->integer('size_bytes')->nullable();
            $table->string('storage_path', 500);
            $table->timestamps();
        });

        Schema::create('parsed_resumes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('resume_id')->constrained('resumes')->cascadeOnDelete();
            $table->longText('raw_text');        // normalized, lowercased
            $table->jsonb('meta')->nullable();   // {pages, language, has_tables}
            $table->jsonb('entities')->nullable(); // {skills:[], edu:[], years_experience:N}
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('parsed_resumes');
        Schema::dropIfExists('resumes');
    }
};

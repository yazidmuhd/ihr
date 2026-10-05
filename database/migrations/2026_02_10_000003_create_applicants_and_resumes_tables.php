<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One applicant profile per applicant user account
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->index();
            $table->string('phone', 30)->nullable();
            $table->string('location')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('github_url')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('website_url')->nullable();
            $table->timestamps();
        });

        Schema::create('resumes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('applicants')->cascadeOnDelete();
            $table->string('filename');                  // original file name shown to users
            $table->string('path');                      // relative path on the "public" disk
            $table->unsignedBigInteger('size')->nullable();
            $table->string('mime', 100)->nullable();
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            // AI parsing pipeline
            $table->string('ai_status', 20)->default('pending');
            $table->jsonb('ai_parsed')->nullable();
            $table->text('ai_error')->nullable();

            $table->timestamps();

            $table->index(['applicant_id', 'sort_order']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE resumes ADD CONSTRAINT resumes_ai_status_check CHECK (ai_status IN ('pending', 'processing', 'done', 'error'))");
            // At most one active resume per applicant
            DB::statement('CREATE UNIQUE INDEX resumes_one_active_per_applicant ON resumes (applicant_id) WHERE is_active');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('resumes');
        Schema::dropIfExists('applicants');
    }
};

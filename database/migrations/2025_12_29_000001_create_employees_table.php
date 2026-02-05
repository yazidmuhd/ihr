<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // If you already have employees table, skip creating
        if (Schema::hasTable('employees')) return;

        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            $table->string('employee_no')->unique();

            $table->unsignedBigInteger('application_id')->nullable()->index();
            $table->unsignedBigInteger('applicant_id')->nullable()->index();
            $table->unsignedBigInteger('vacancy_id')->nullable()->index();
            $table->unsignedBigInteger('interview_id')->nullable()->index();

            $table->string('name');
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();

            $table->string('job_title')->nullable();
            $table->string('department')->nullable();

            $table->timestamp('hired_at')->nullable();
            $table->string('status')->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};

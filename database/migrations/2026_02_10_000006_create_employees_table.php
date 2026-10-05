<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hired candidates. Links back to the recruitment record are kept (SET NULL)
        // so an employee survives if old applications are purged.
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_no', 30)->unique();
            $table->string('name');
            $table->string('email')->nullable()->index();
            $table->string('phone', 30)->nullable();
            $table->string('job_title')->nullable();
            $table->string('department', 120)->nullable()->index();
            $table->timestamp('hired_at')->nullable();
            $table->string('status', 20)->default('active');

            $table->foreignId('applicant_id')->nullable()->constrained('applicants')->nullOnDelete();
            $table->foreignId('application_id')->nullable()->unique()->constrained('applications')->nullOnDelete();
            $table->foreignId('vacancy_id')->nullable()->constrained('vacancies')->nullOnDelete();
            $table->foreignId('interview_id')->nullable()->unique()->constrained('interviews')->nullOnDelete();
            $table->foreignId('hr_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->foreign('department')->references('name')->on('departments')
                ->cascadeOnUpdate()->restrictOnDelete();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE employees ADD CONSTRAINT employees_status_check CHECK (status IN ('active', 'probation', 'on_leave', 'resigned', 'terminated'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};

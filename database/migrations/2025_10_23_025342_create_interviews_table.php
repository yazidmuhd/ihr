<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('interviews')) {
            Schema::create('interviews', function (Blueprint $table) {
                $table->bigIncrements('id');

                // FK: applications(id)
                $table->unsignedBigInteger('application_id');
                $table->foreign('application_id')
                      ->references('id')->on('applications')
                      ->onDelete('cascade');

                $table->timestamp('scheduled_at');                  // interview time
                $table->string('mode', 16);                         // Online | Onsite
                $table->string('location', 500)->nullable();        // for Onsite
                $table->string('meeting_link', 500)->nullable();    // for Online
                $table->string('status', 16)->default('Scheduled'); // Scheduled | Completed | Cancelled

                $table->timestamps();

                // Helpful indexes
                $table->index('application_id');
                $table->index('scheduled_at');
                $table->index('status');
            });

            // Postgres CHECK constraints for allowed values
            // (safe if you are on PostgreSQL; ignored on other DBs)
            DB::statement("ALTER TABLE interviews
                ADD CONSTRAINT interviews_mode_check
                CHECK (mode IN ('Online','Onsite'))");

            DB::statement("ALTER TABLE interviews
                ADD CONSTRAINT interviews_status_check
                CHECK (status IN ('Scheduled','Completed','Cancelled'))");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('interviews')) {
            Schema::drop('interviews');
        }
    }
};

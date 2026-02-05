<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;


return new class extends Migration {
    public function up(): void
    {
        // If old notifications table was renamed, its indexes may still be named "notifications_*"
DB::statement("ALTER INDEX IF EXISTS notifications_notifiable_type_notifiable_id_index RENAME TO interview_notifications_notifiable_type_notifiable_id_index");
DB::statement("ALTER INDEX IF EXISTS notifications_read_at_index RENAME TO interview_notifications_read_at_index");
DB::statement("ALTER INDEX IF EXISTS notifications_type_index RENAME TO interview_notifications_type_index");

// If a previous failed run left a partially created notifications table, clean it
DB::statement("DROP TABLE IF EXISTS notifications CASCADE");

        /**
         * 1) Rename your existing custom notifications table
         *    (interview_id, message, recipient_applicant_id...)
         */
        if (Schema::hasTable('notifications') && !Schema::hasTable('interview_notifications')) {
            Schema::rename('notifications', 'interview_notifications');
        }

        /**
         * 2) Create Laravel's default notifications table
         *    used by $user->notify() + DatabaseChannel
         */
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable'); // notifiable_type + notifiable_id
                $table->text('data');         // JSON stored as text
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

            });
        }
    }

    public function down(): void
    {
        // Drop Laravel table
        if (Schema::hasTable('notifications')) {
            Schema::drop('notifications');
        }

        // Rename interview_notifications back (if it exists)
        if (Schema::hasTable('interview_notifications') && !Schema::hasTable('notifications')) {
            Schema::rename('interview_notifications', 'notifications');
        }
    }
};

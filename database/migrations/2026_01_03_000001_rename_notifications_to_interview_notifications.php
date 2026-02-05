<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('notifications') && !Schema::hasTable('interview_notifications')) {
            Schema::rename('notifications', 'interview_notifications');
        }
    }

    public function down(): void {
        if (Schema::hasTable('interview_notifications') && !Schema::hasTable('notifications')) {
            Schema::rename('interview_notifications', 'notifications');
        }
    }
};

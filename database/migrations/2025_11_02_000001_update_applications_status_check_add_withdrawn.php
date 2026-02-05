<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Adjust this list if your app uses different status values
    private string $constraint = 'applications_status_check';
    private array $statusesUp   = ['submitted','in_review','shortlisted','rejected','hired','withdrawn'];
    private array $statusesDown = ['submitted','in_review','shortlisted','rejected','hired'];

    public function up(): void
    {
        // Drop existing check and recreate with withdrawn included
        DB::statement("ALTER TABLE applications DROP CONSTRAINT IF EXISTS {$this->constraint}");
        $in = implode("','", $this->statusesUp);
        DB::statement("
            ALTER TABLE applications
            ADD CONSTRAINT {$this->constraint}
            CHECK (status IN ('{$in}'))
        ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE applications DROP CONSTRAINT IF EXISTS {$this->constraint}");
        $in = implode("','", $this->statusesDown);
        DB::statement("
            ALTER TABLE applications
            ADD CONSTRAINT {$this->constraint}
            CHECK (status IN ('{$in}'))
        ");
    }
};

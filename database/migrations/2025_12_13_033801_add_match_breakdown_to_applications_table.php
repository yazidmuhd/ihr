<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // user_id already exists in applicants table (no-op)
    }

    public function down(): void
    {
        // no-op (don't drop existing column)
    }
};

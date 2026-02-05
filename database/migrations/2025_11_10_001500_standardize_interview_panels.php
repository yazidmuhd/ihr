<?php

// database/migrations/2025_11_10_001500_standardize_interview_panels.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Postgres-safe: rename 'panel'->'panel_no' if it exists; add if missing; add unique key
        DB::statement(<<<'SQL'
DO $$
BEGIN
  -- rename old 'panel' to 'panel_no' if present
  IF EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name='interview_panels' AND column_name='panel'
  ) THEN
    EXECUTE 'ALTER TABLE interview_panels RENAME COLUMN panel TO panel_no';
  END IF;

  -- add panel_no if still missing
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name='interview_panels' AND column_name='panel_no'
  ) THEN
    EXECUTE 'ALTER TABLE interview_panels ADD COLUMN panel_no integer NOT NULL DEFAULT 1';
  END IF;

  -- add notes if you earlier used "note"
  IF EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name='interview_panels' AND column_name='note'
  ) AND NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_name='interview_panels' AND column_name='notes'
  ) THEN
    EXECUTE 'ALTER TABLE interview_panels RENAME COLUMN note TO notes';
  END IF;

  -- unique (interview_id, panel_no) to make upserts deterministic
  IF NOT EXISTS (
    SELECT 1 FROM pg_constraint
    WHERE conname = 'interview_panels_interview_panel_unique'
  ) THEN
    EXECUTE 'ALTER TABLE interview_panels
             ADD CONSTRAINT interview_panels_interview_panel_unique
             UNIQUE (interview_id, panel_no)';
  END IF;
END $$;
SQL);
    }

    public function down(): void
    {
        // optional: drop unique constraint only
        DB::statement("ALTER TABLE interview_panels DROP CONSTRAINT IF EXISTS interview_panels_interview_panel_unique");
        // (we generally keep panel_no as-is)
    }
};

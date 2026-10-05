<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Tables whose inserts / updates / deletes are recorded */
    private array $audited = ['users', 'departments', 'vacancies', 'applications', 'interviews', 'interview_panels', 'employees'];

    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('table_name', 64);
            $table->unsignedBigInteger('record_id');
            $table->string('action', 10);
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['table_name', 'record_id']);
            $table->index('created_at');
        });

        if (DB::getDriverName() !== 'pgsql') return;

        // The acting user comes from the "app.user_id" session setting
        // (set per request by the SetAuditUser middleware). Secrets are never logged,
        // and updates that change nothing but updated_at are skipped.
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION write_audit_log() RETURNS trigger AS $$
            DECLARE
                old_row jsonb := CASE WHEN TG_OP <> 'INSERT' THEN to_jsonb(OLD) - 'password' - 'remember_token' END;
                new_row jsonb := CASE WHEN TG_OP <> 'DELETE' THEN to_jsonb(NEW) - 'password' - 'remember_token' END;
            BEGIN
                IF TG_OP = 'UPDATE' AND (old_row - 'updated_at') = (new_row - 'updated_at') THEN
                    RETURN NEW;
                END IF;

                INSERT INTO audit_logs (user_id, table_name, record_id, action, old_values, new_values, created_at)
                VALUES (
                    NULLIF(current_setting('app.user_id', true), '')::bigint,
                    TG_TABLE_NAME,
                    COALESCE(new_row->>'id', old_row->>'id')::bigint,
                    lower(TG_OP),
                    old_row,
                    new_row,
                    now()
                );

                RETURN COALESCE(NEW, OLD);
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        foreach ($this->audited as $table) {
            DB::unprepared("CREATE TRIGGER {$table}_audit AFTER INSERT OR UPDATE OR DELETE ON {$table} FOR EACH ROW EXECUTE FUNCTION write_audit_log()");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            foreach ($this->audited as $table) {
                DB::unprepared("DROP TRIGGER IF EXISTS {$table}_audit ON {$table}");
            }
            DB::unprepared('DROP FUNCTION IF EXISTS write_audit_log()');
        }
        Schema::dropIfExists('audit_logs');
    }
};

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Constraints and triggers that protect the data even if application code is wrong */
class DatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function assertRejected(callable $write, string $constraint): void
    {
        try {
            DB::transaction($write);
            $this->fail("Expected the database to reject this write ({$constraint}).");
        } catch (QueryException $e) {
            $this->assertStringContainsString($constraint, $e->getMessage());
        }
    }

    public function test_status_values_are_checked(): void
    {
        [, $applicantId] = $this->applicant();
        $vacancyId = $this->vacancy();
        $appId = DB::table('applications')->insertGetId(['applicant_id' => $applicantId, 'vacancy_id' => $vacancyId]);

        $this->assertRejected(fn () => DB::table('vacancies')->where('id', $vacancyId)->update(['status' => 'open']), 'vacancies_status_check');
        $this->assertRejected(fn () => DB::table('applications')->where('id', $appId)->update(['status' => 'approved']), 'applications_status_check');
        $this->assertRejected(fn () => DB::table('applications')->where('id', $appId)->update(['match_score' => 120]), 'applications_match_score_range');
    }

    public function test_role_and_is_hr_must_agree(): void
    {
        $this->assertRejected(fn () => User::factory()->create(['role' => 'applicant', 'is_hr' => true]), 'users_is_hr_matches_role');
    }

    public function test_only_one_active_resume_per_applicant(): void
    {
        [, $applicantId] = $this->applicant();
        $this->parsedResume($applicantId);

        $this->assertRejected(fn () => $this->parsedResume($applicantId), 'resumes_one_active_per_applicant');
    }

    public function test_departments_in_use_cannot_be_deleted_but_renames_cascade(): void
    {
        $vacancyId = $this->vacancy();   // department "IT"

        $this->assertRejected(fn () => DB::table('departments')->where('name', 'IT')->delete(), 'vacancies_department_foreign');

        DB::table('departments')->where('name', 'IT')->update(['name' => 'Information Technology']);
        $this->assertSame('Information Technology', DB::table('vacancies')->where('id', $vacancyId)->value('department'));
    }

    public function test_audit_log_records_the_acting_user_and_never_passwords(): void
    {
        $hr = $this->hrUser();
        $vacancyId = $this->vacancy();

        $this->actingAs($hr)->patch("/hr/vacancies/{$vacancyId}/status", ['status' => 'Closed'])->assertRedirect();

        $log = DB::table('audit_logs')->where('table_name', 'vacancies')->where('action', 'update')->latest('id')->first();
        $this->assertSame($hr->id, (int) $log->user_id);
        $this->assertSame('Closed', json_decode($log->new_values, true)['status']);

        $this->assertSame(0, DB::table('audit_logs')->whereRaw("jsonb_exists(coalesce(new_values, '{}'), 'password') or jsonb_exists(coalesce(old_values, '{}'), 'password')")->count());
    }

    public function test_touch_only_updates_are_not_audited(): void
    {
        $vacancyId = $this->vacancy();
        $before = DB::table('audit_logs')->count();

        DB::table('vacancies')->where('id', $vacancyId)->update(['updated_at' => now()->addMinute()]);

        $this->assertSame($before, DB::table('audit_logs')->count());
    }
}

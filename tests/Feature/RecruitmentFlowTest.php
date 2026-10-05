<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Apply → AI score → shortlist → interview → panel rating → finalize → hire,
 * through the real HTTP routes, checking the status history and audit trail.
 */
class RecruitmentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();   // no e-mails / database notifications during tests
    }

    public function test_hr_creates_a_vacancy_and_a_new_department_is_registered(): void
    {
        $this->actingAs($this->hrUser())->post('/hr/vacancies', [
            'title'                => 'Data Analyst',
            'department'           => 'Data Science',
            'employment_type'      => 'contract',
            'status'               => 'Open',
            'skills_required'      => ['SQL', 'Python'],
            'experience_min_years' => 3,
            'experience_max_years' => 1,   // swapped by the controller
        ])->assertRedirect();

        $this->assertDatabaseHas('departments', ['name' => 'Data Science']);
        $this->assertDatabaseHas('vacancies', [
            'title' => 'Data Analyst', 'department' => 'Data Science',
            'experience_min_years' => 1, 'experience_max_years' => 3,
        ]);
    }

    public function test_full_hiring_pipeline(): void
    {
        $hr = $this->hrUser();
        [$user, $applicantId] = $this->applicant();
        $resumeId  = $this->parsedResume($applicantId);
        $vacancyId = $this->vacancy();

        // Applicant applies and is scored immediately
        $this->actingAs($user)->post('/applications', ['vacancy_id' => $vacancyId])->assertRedirect();

        $app = DB::table('applications')->where('applicant_id', $applicantId)->first();
        $this->assertSame('submitted', $app->status);
        $this->assertSame($resumeId, (int) $app->resume_id);
        $this->assertGreaterThan(0, (float) $app->match_score);

        // HR rescore produces the same score from resumes.ai_parsed
        $score = (float) $app->match_score;
        DB::table('applications')->where('id', $app->id)->update(['match_score' => null, 'match_breakdown' => null]);
        $this->actingAs($hr)->post("/hr/vacancies/{$vacancyId}/ai/rescore")->assertRedirect();
        $this->assertEquals($score, (float) DB::table('applications')->where('id', $app->id)->value('match_score'));

        // Shortlist and invite (mode is normalised: "Online" → "online")
        $this->actingAs($hr)->post("/hr/applications/{$app->id}/decision", ['decision' => 'shortlisted'])->assertRedirect();
        $this->actingAs($hr)->post('/hr/interviews/upsert', [
            'application_id' => $app->id,
            'scheduled_at'   => '2026-11-20 10:00:00',
            'mode'           => 'Online',
        ])->assertRedirect();

        $interview = DB::table('interviews')->where('application_id', $app->id)->first();
        $this->assertSame('online', $interview->mode);
        $this->assertSame('scheduled', $interview->status);

        // Candidate confirms
        $this->actingAs($user)->post("/app/interviews/{$interview->id}/respond", ['response' => 'confirm'])->assertRedirect();
        $this->assertSame('confirmed', DB::table('interviews')->where('id', $interview->id)->value('candidate_status'));

        // Evaluation is locked until the interview is marked done
        $this->actingAs($hr)->post("/hr/interviews/{$interview->id}/panels", ['panel_count' => 2])->assertForbidden();
        $this->actingAs($hr)->patch("/hr/interviews/{$interview->id}/status", ['status' => 'done'])->assertRedirect();

        $this->actingAs($hr)->post("/hr/interviews/{$interview->id}/panels", ['panel_count' => 2])->assertRedirect();
        $this->actingAs($hr)->post("/hr/interviews/{$interview->id}/rate", ['panel_no' => 1, 'rating' => 5])->assertRedirect();
        $this->actingAs($hr)->post("/hr/interviews/{$interview->id}/rate", ['panel_no' => 2, 'rating' => 4])->assertRedirect();
        $this->actingAs($hr)->patch("/hr/interviews/{$interview->id}/finalize")->assertRedirect();

        $interview = DB::table('interviews')->where('id', $interview->id)->first();
        $this->assertSame('scored', $interview->status);
        $this->assertSame(90, (int) $interview->interview_score);   // average 4.5 / 5

        // Hire
        $this->actingAs($hr)->post("/hr/interviews/{$interview->id}/hire")->assertRedirect('/hr/employees');

        $employee = DB::table('employees')->where('application_id', $app->id)->first();
        $this->assertNotNull($employee);
        $this->assertSame('IT', $employee->department);
        $this->assertSame($hr->id, (int) $employee->hr_user_id);
        $this->assertSame('hired', DB::table('applications')->where('id', $app->id)->value('status'));

        // Hiring twice does not create a second employee
        $this->actingAs($hr)->post("/hr/interviews/{$interview->id}/hire")->assertRedirect('/hr/employees');
        $this->assertSame(1, DB::table('employees')->count());

        // Status history: who moved the application, in order
        $history = DB::table('application_status_histories')->where('application_id', $app->id)->orderBy('id')->get();
        $this->assertSame([null, 'submitted', 'shortlisted'], $history->pluck('from_status')->all());
        $this->assertSame(['submitted', 'shortlisted', 'hired'], $history->pluck('to_status')->all());
        $this->assertSame([$user->id, $hr->id, $hr->id], $history->pluck('changed_by')->map(fn ($v) => (int) $v)->all());

        // The employee page and the activity page show it
        $this->actingAs($hr)->get("/hr/employees/{$employee->id}")->assertOk();
        $this->actingAs($hr)->get("/hr/activity?application={$app->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('HR/Activity/Index')->has('timeline', 3));
    }

    public function test_applicant_cannot_apply_twice_or_to_a_closed_vacancy(): void
    {
        [$user, $applicantId] = $this->applicant();
        $this->parsedResume($applicantId);
        $open   = $this->vacancy();
        $closed = $this->vacancy(['title' => 'Closed role', 'status' => 'Closed']);

        $this->actingAs($user)->post('/applications', ['vacancy_id' => $open]);
        $this->actingAs($user)->post('/applications', ['vacancy_id' => $open]);
        $this->actingAs($user)->post('/applications', ['vacancy_id' => $closed]);

        $this->assertSame(1, DB::table('applications')->where('applicant_id', $applicantId)->count());
    }

    public function test_applicant_without_a_parsed_resume_is_sent_to_the_resume_page(): void
    {
        [$user] = $this->applicant();

        $this->actingAs($user)->post('/applications', ['vacancy_id' => $this->vacancy()])
            ->assertRedirect('/app/resume');
    }
}

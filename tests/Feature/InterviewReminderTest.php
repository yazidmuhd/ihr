<?php

namespace Tests\Feature;

use App\Notifications\InterviewReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InterviewReminderTest extends TestCase
{
    use RefreshDatabase;

    private function interview(array $attributes = []): array
    {
        [$user, $applicantId] = $this->applicant();
        $vacancyId = $this->vacancy();
        $appId = DB::table('applications')->insertGetId(['applicant_id' => $applicantId, 'vacancy_id' => $vacancyId, 'status' => 'shortlisted']);

        $id = DB::table('interviews')->insertGetId($attributes + [
            'application_id' => $appId,
            'applicant_id'   => $applicantId,
            'vacancy_id'     => $vacancyId,
            'scheduled_at'   => now()->addDays(2),
            'status'         => 'scheduled',
            'mode'           => 'online',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return [$user, $id];
    }

    public function test_applicant_is_reminded_once_about_an_interview_in_the_next_three_days(): void
    {
        Notification::fake();
        [$user, $id] = $this->interview();

        $this->artisan('app:send-interview-reminders')->assertSuccessful();
        $this->artisan('app:send-interview-reminders')->assertSuccessful();

        Notification::assertSentToTimes($user, InterviewReminder::class, 1);
        $this->assertNotNull(DB::table('interviews')->where('id', $id)->value('reminder_3d_sent_at'));
    }

    public function test_no_reminder_for_declined_far_off_or_cancelled_interviews(): void
    {
        Notification::fake();
        [$declined] = $this->interview(['candidate_status' => 'declined']);
        [$later]    = $this->interview(['scheduled_at' => now()->addDays(10)]);
        [$cancel]   = $this->interview(['status' => 'cancelled']);

        $this->artisan('app:send-interview-reminders')->assertSuccessful();

        Notification::assertNothingSentTo([$declined, $later, $cancel]);
    }

    public function test_reminders_are_scheduled_hourly(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('app:send-interview-reminders');
    }
}

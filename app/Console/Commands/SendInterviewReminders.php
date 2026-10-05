<?php

namespace App\Console\Commands;

use App\Models\Interview;
use App\Notifications\InterviewReminder;
use Illuminate\Console\Command;

/**
 * Reminds applicants of interviews in the next 3 days, once per interview.
 * Scheduled hourly (routes/console.php); runs in the worker container.
 */
class SendInterviewReminders extends Command
{
    protected $signature = 'app:send-interview-reminders';

    protected $description = 'Notify applicants about interviews scheduled in the next 3 days';

    public function handle(): int
    {
        $interviews = Interview::with('application.applicant.user', 'vacancy')
            ->whereBetween('scheduled_at', [now(), now()->addDays(3)])
            ->where('status', 'scheduled')
            ->whereNotIn('candidate_status', ['declined', 'cancelled_by_hr'])
            ->whereNull('reminder_3d_sent_at')
            ->get();

        foreach ($interviews as $interview) {
            $user = $interview->application?->applicant?->user;
            if (!$user) continue;

            $user->notify(new InterviewReminder(
                interviewId: $interview->id,
                vacancyTitle: $interview->vacancy?->title ?? 'Vacancy',
                scheduledAtIso: $interview->scheduled_at->toIso8601String(),
            ));

            $interview->forceFill(['reminder_3d_sent_at' => now()])->save();
        }

        $this->info("Sent {$interviews->count()} interview reminder(s).");

        return self::SUCCESS;
    }
}

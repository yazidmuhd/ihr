<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendInterviewReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-interview-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
{
    $targetFrom = now()->addDays(3)->startOfDay();
    $targetTo   = now()->addDays(3)->endOfDay();

    $interviews = \App\Models\Interview::with('application.applicant.user', 'vacancy')
        ->whereBetween('scheduled_at', [$targetFrom, $targetTo])
        ->where('status', 'scheduled')
        ->whereNull('reminder_3d_sent_at')
        ->get();

    foreach ($interviews as $i) {
        $u = $i->application?->applicant?->user;
        if (!$u) continue;

        $u->notify(new \App\Notifications\InterviewReminder(
            interviewId: $i->id,
            vacancyTitle: $i->vacancy?->title ?? 'Vacancy',
            scheduledAtIso: optional($i->scheduled_at)->toIso8601String(),
        ));

        $i->reminder_3d_sent_at = now();
        $i->save();
    }

    return 0;
}

}

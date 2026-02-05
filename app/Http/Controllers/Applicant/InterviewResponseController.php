<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\InterviewOutcome;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InterviewResponseController extends Controller
{
    public function respond(Request $req, int $id)
    {
        $user = $req->user();
        abort_unless($user, 403);

        $data = $req->validate([
            'response' => 'required|string|in:confirmed,declined',
            'reason'   => 'nullable|string|max:1000',
        ]);

        $interview = DB::table('interviews')->where('id', $id)->first();
        abort_unless($interview, 404, 'Interview not found.');

        // Update interview candidate status
        $upd = ['updated_at' => now()];

        if (Schema::hasColumn('interviews', 'candidate_status')) {
            $upd['candidate_status'] = $data['response'];
        }

        if (Schema::hasColumn('interviews', 'candidate_reason')) {
            $upd['candidate_reason'] = $data['reason'] ?? null;
        }

        DB::table('interviews')->where('id', $id)->update($upd);

        // Get application + vacancy title for nicer notif
        $app = DB::table('applications')->where('id', $interview->application_id)->first();
        $vacancyTitle = 'Vacancy';
        $vacancyId = null;

        if ($app && isset($app->vacancy_id) && Schema::hasTable('vacancies')) {
            $vacancyId = (int) $app->vacancy_id;
            $vacancyTitle = DB::table('vacancies')->where('id', $vacancyId)->value('title') ?: 'Vacancy';
        }

        // Notify HR staff (who invited)
        $hrUserId = null;
        if (Schema::hasColumn('interviews', 'hr_user_id')) {
            $hrUserId = $interview->hr_user_id ?? null;
        }

        if ($hrUserId) {
            $hr = User::find($hrUserId);
            if ($hr) {
                $hr->notify(new InterviewOutcome(
                    interviewId: (int)$id,
                    applicationId: (int)($interview->application_id ?? 0),
                    vacancyId: $vacancyId,
                    vacancyTitle: $vacancyTitle,
                    outcome: $data['response'],          // confirmed / declined
                    scheduledAt: $interview->scheduled_at ?? null,
                    mode: $interview->mode ?? null,
                    location: $interview->location ?? null,
                    meetingLink: $interview->meeting_link ?? null,
                    extraInfo: $data['reason'] ?? null,
                    celebrate: false,
                    actor: 'Candidate'
                ));
            }
        }

        return back()->with('status', 'Your response has been sent to HR.');
    }
}

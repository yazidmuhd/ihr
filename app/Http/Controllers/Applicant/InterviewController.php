<?php

namespace App\Http\Controllers\Applicant;

use App\Support\DbSchema;
use App\Http\Controllers\Concerns\ResolvesApplicant;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\InterviewResponseReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class InterviewController extends Controller
{
    use ResolvesApplicant;

    public function index(): Response
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $applicantId = $this->resolveApplicantId(false);

        if (!$applicantId) {
            return Inertia::render('Applicant/Interviews/Index', [
                'interviews' => [],
            ]);
        }

        // Interviews for THIS applicant (via applications)
        $interviews = DB::table('interviews as i')
            ->join('applications as a', 'a.id', '=', 'i.application_id')
            ->join('vacancies as v', 'v.id', '=', 'a.vacancy_id')
            ->where('a.applicant_id', $applicantId)
            ->orderByRaw("CASE WHEN i.scheduled_at IS NULL THEN 1 ELSE 0 END, i.scheduled_at ASC, i.id ASC")
            ->get([
                'i.id',
                'i.status',
                'i.mode',
                'i.location',
                'i.meeting_link',
                'i.extra_info',
                'i.candidate_status',
                'i.candidate_reason',

                // helper raw timestamp for reminder checks
                'i.scheduled_at as scheduled_at_raw',

                // ISO for frontend (safe)
                DB::raw("to_char(i.scheduled_at, 'YYYY-MM-DD\"T\"HH24:MI:SS') as scheduled_at"),

                'v.title as vacancy_title',
                'a.id as application_id',
                'a.vacancy_id',
                'a.applicant_id',
            ]);

        // Remove helper-only field before sending to frontend
        $interviews = $interviews->map(function ($x) {
            unset($x->scheduled_at_raw);
            return $x;
        });

        return Inertia::render('Applicant/Interviews/Index', [
            'interviews' => $interviews,
        ]);
    }

    public function respond(Request $request, int $id)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $data = $request->validate([
            'response' => 'required|string|in:confirm,decline',
            'reason'   => 'nullable|string|max:500',
        ]);

        $applicantId = $this->resolveApplicantId(false);
        abort_if(!$applicantId, 403);

        // Ensure this interview belongs to this applicant + grab info for notification
        $select = [
            'i.id as interview_id',
            'i.status as interview_status',
            'i.mode',
            'i.location',
            'i.meeting_link',
            'i.extra_info',
            'i.scheduled_at',
            'i.candidate_status',
            'i.candidate_reason',
            'a.id as application_id',
            'a.vacancy_id',
            'a.applicant_id',
            'v.title as vacancy_title',
        ];

        // if interviews.created_by exists, pull it (helps notify the inviter)
        if (DbSchema::hasColumn('interviews', 'created_by')) {
            $select[] = 'i.created_by';
        }

        $row = DB::table('interviews as i')
            ->join('applications as a', 'a.id', '=', 'i.application_id')
            ->join('vacancies as v', 'v.id', '=', 'a.vacancy_id')
            ->where('i.id', $id)
            ->where('a.applicant_id', $applicantId)
            ->first($select);

        abort_if(!$row, 404);

        $current = strtolower((string) ($row->candidate_status ?? ''));
        if ($current !== '' && $current !== 'pending') {
            return back()->with('status', 'Your response is already recorded.');
        }

        $now = now();

        if ($data['response'] === 'confirm') {
            $upd = [
                'updated_at' => $now,
            ];
            if (DbSchema::hasColumn('interviews', 'candidate_status')) $upd['candidate_status'] = 'confirmed';
            if (DbSchema::hasColumn('interviews', 'candidate_reason')) $upd['candidate_reason'] = null;

            DB::table('interviews')->where('id', $id)->update($upd);

            // ✅ Notify HR (alert + email)
            $this->notifyHrInterviewResponseReceived([
                'interview_id'   => (int) $row->interview_id,
                'application_id' => (int) $row->application_id,
                'vacancy_id'     => (int) ($row->vacancy_id ?? 0),
                'vacancy_title'  => (string) ($row->vacancy_title ?? 'Vacancy'),
                'applicant_id'   => (int) ($row->applicant_id ?? 0),
                'response'       => 'confirmed',
                'reason'         => null,
                'scheduled_at'   => $row->scheduled_at,
                'mode'           => $row->mode ?? null,
                'location'       => $row->location ?? null,
                'meeting_link'   => $row->meeting_link ?? null,
                'created_by'     => isset($row->created_by) ? (int)$row->created_by : null,
            ]);

            return back()->with('status', 'Interview confirmed.');
        }

        // decline
        $reason = $data['reason'] ?? null;

        $upd = [
            'updated_at' => $now,
        ];
        if (DbSchema::hasColumn('interviews', 'candidate_status')) $upd['candidate_status'] = 'declined';
        if (DbSchema::hasColumn('interviews', 'candidate_reason')) $upd['candidate_reason'] = $reason;

        DB::table('interviews')->where('id', $id)->update($upd);

        // ✅ Notify HR (alert + email)
        $this->notifyHrInterviewResponseReceived([
            'interview_id'   => (int) $row->interview_id,
            'application_id' => (int) $row->application_id,
            'vacancy_id'     => (int) ($row->vacancy_id ?? 0),
            'vacancy_title'  => (string) ($row->vacancy_title ?? 'Vacancy'),
            'applicant_id'   => (int) ($row->applicant_id ?? 0),
            'response'       => 'declined',
            'reason'         => $reason,
            'scheduled_at'   => $row->scheduled_at,
            'mode'           => $row->mode ?? null,
            'location'       => $row->location ?? null,
            'meeting_link'   => $row->meeting_link ?? null,
            'created_by'     => isset($row->created_by) ? (int)$row->created_by : null,
        ]);

        return back()->with('status', 'Interview declined.');
    }

    /**
     * ✅ Resolve applicant display name for HR email/alert.
     */
    protected function resolveApplicantName(int $applicantId): string
    {
        $user = Auth::user();

        try {
            if (DbSchema::hasTable('applicants')) {
                if (DbSchema::hasColumn('applicants', 'name')) {
                    $nm = DB::table('applicants')->where('id', $applicantId)->value('name');
                    if ($nm) return (string) $nm;
                }
                if (DbSchema::hasColumn('applicants', 'full_name')) {
                    $nm = DB::table('applicants')->where('id', $applicantId)->value('full_name');
                    if ($nm) return (string) $nm;
                }

                $hasFirst = DbSchema::hasColumn('applicants', 'first_name');
                $hasLast  = DbSchema::hasColumn('applicants', 'last_name');
                if ($hasFirst || $hasLast) {
                    $row = DB::table('applicants')->where('id', $applicantId)->first(['first_name', 'last_name']);
                    $nm = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
                    if ($nm !== '') return $nm;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        if ($user && !empty($user->name)) return (string) $user->name;

        return "Applicant #{$applicantId}";
    }

    /**
     * ✅ Find HR recipients (schema-safe) + notify InterviewResponseReceived (alert + email).
     * Priority:
     * - interview.created_by (inviter) if provided
     * - HR users by is_hr / role / user_type
     * - vacancy owner (created_by/user_id/hr_user_id/owner_user_id)
     */
    protected function notifyHrInterviewResponseReceived(array $payload): void
    {
        try {
            if (!DbSchema::hasTable('users')) return;

            $ids = [];

            // 0) Prefer inviter (created_by from interviews)
            if (!empty($payload['created_by'])) {
                $ids[] = (int) $payload['created_by'];
            }

            // 1) HR by columns if exist
            if (DbSchema::hasColumn('users', 'is_hr')) {
                $ids = array_merge($ids, DB::table('users')->where('is_hr', true)->pluck('id')->all());
            }

            if (DbSchema::hasColumn('users', 'role')) {
                $ids = array_merge($ids, DB::table('users')
                    ->whereIn(DB::raw('lower(role)'), ['hr', 'hr_staff', 'hr-admin', 'admin', 'superadmin'])
                    ->pluck('id')->all());
            }

            if (DbSchema::hasColumn('users', 'user_type')) {
                $ids = array_merge($ids, DB::table('users')
                    ->whereIn(DB::raw('lower(user_type)'), ['hr', 'staff', 'admin'])
                    ->pluck('id')->all());
            }

            // 2) Vacancy owner column
            if (!empty($payload['vacancy_id']) && DbSchema::hasTable('vacancies')) {
                foreach (['created_by', 'user_id', 'hr_user_id', 'owner_user_id'] as $col) {
                    if (DbSchema::hasColumn('vacancies', $col)) {
                        $ownerId = DB::table('vacancies')->where('id', $payload['vacancy_id'])->value($col);
                        if ($ownerId) $ids[] = (int) $ownerId;
                        break;
                    }
                }
            }

            $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
            if (!$ids) return;

            $applicantId = (int) ($payload['applicant_id'] ?? 0);
            $applicantName = $applicantId ? $this->resolveApplicantName($applicantId) : null;

            $users = User::whereIn('id', $ids)->get();
            foreach ($users as $u) {
                $u->notify(new InterviewResponseReceived(
                    interviewId: (int) ($payload['interview_id'] ?? 0),
                    applicationId: (int) ($payload['application_id'] ?? 0),
                    applicantId: $applicantId,
                    vacancyId: (int) ($payload['vacancy_id'] ?? 0),
                    vacancyTitle: (string) ($payload['vacancy_title'] ?? 'Vacancy'),
                    response: (string) ($payload['response'] ?? 'confirmed'),
                    reason: $payload['reason'] ?? null,
                    scheduledAt: $payload['scheduled_at'] ?? null,
                    mode: $payload['mode'] ?? null,
                    location: $payload['location'] ?? null,
                    meetingLink: $payload['meeting_link'] ?? null,
                    applicantName: $applicantName
                ));
            }
        } catch (\Throwable $e) {
            // never break applicant flow
            Log::warning('notifyHrInterviewResponseReceived failed', ['error' => $e->getMessage()]);
        }
    }

}

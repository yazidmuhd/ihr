<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\InterviewInvited;
use App\Notifications\InterviewOutcome;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class InterviewController extends Controller
{
    /** first matching column helper (table-safe) */
    protected function col(string $t, string ...$names): ?string
    {
        if (!Schema::hasTable($t)) return null;
        foreach ($names as $n) {
            if (Schema::hasColumn($t, $n)) return $n;
        }
        return null;
    }

    protected function anon(int $id): string
    {
        return 'Candidate #' . strtoupper(substr(sha1('i-hr' . $id), 0, 6));
    }

    protected function isUrl(?string $v): bool
    {
        if (!$v) return false;
        return str_starts_with($v, 'http://') || str_starts_with($v, 'https://');
    }

    protected function buildPublicUrl(?string $pathOrUrl): ?string
    {
        if (!$pathOrUrl) return null;
        if ($this->isUrl($pathOrUrl)) return $pathOrUrl;

        $p = ltrim($pathOrUrl, '/');
        if (str_starts_with($p, 'storage/')) return '/' . $p;

        return Storage::disk('public')->url($p);
    }

    /** Normalize interview status to DB-safe values */
    protected function normalizeInterviewStatus(string $value): string
    {
        $s = strtolower(trim($value));
        $s = str_replace([' ', '-'], '_', $s);

        $map = [
            'completed' => 'done',
            'complete'  => 'done',
            'canceled'  => 'cancelled',
            'noshow'    => 'no_show',
            'no-show'   => 'no_show',
        ];

        return $map[$s] ?? $s;
    }

    /** DB-safe allowed statuses (must match your CHECK constraint) */
    protected function allowedInterviewStatuses(): array
    {
        return ['invited', 'scheduled', 'done', 'scored', 'no_show', 'cancelled'];
    }

    /** Enforce evaluation actions only after interview is done/scored */
    protected function ensureEvaluationUnlocked(object $interview): void
    {
        $st = $this->normalizeInterviewStatus((string)($interview->status ?? ''));
        abort_unless(in_array($st, ['done', 'scored'], true), 403, 'Evaluation is locked until interview is marked Done.');
    }

    /** interview_panels panel_no column (your DB has panel_no) */
    protected function panelNoColumn(): string
    {
        if (Schema::hasTable('interview_panels') && Schema::hasColumn('interview_panels', 'panel_no')) return 'panel_no';
        abort(500, 'interview_panels.panel_no column not found.');
    }

    /** Use rating if exists, else fallback to stars */
    protected function panelScoreColumn(): string
    {
        if (Schema::hasTable('interview_panels') && Schema::hasColumn('interview_panels', 'rating')) return 'rating';
        if (Schema::hasTable('interview_panels') && Schema::hasColumn('interview_panels', 'stars')) return 'stars';
        abort(500, 'interview_panels must have rating or stars column.');
    }

    /** Defaults for interview_panels (match your schema) */
    protected function panelDefaults(int $panelNo): array
    {
        $d = [];

        if (Schema::hasColumn('interview_panels', 'name')) {
            $d['name'] = "Panel {$panelNo}";
        }
        // department is nullable in your table; leave it null
        if (Schema::hasColumn('interview_panels', 'stars')) {
            $d['stars'] = 0;
        }
        // rating is nullable, but we can default to null to avoid mixing with real 1..5
        if (Schema::hasColumn('interview_panels', 'rating')) {
            $d['rating'] = null;
        }

        return $d;
    }

    /** ✅ Helper: left join employees to mark is_employee */
    protected function applyEmployeeJoin($q, string $aliasInterview = 'i', string $aliasApp = 'a'): void
    {
        if (!Schema::hasTable('employees')) return;

        $e = 'employees';

        $hasInterviewId = Schema::hasColumn($e, 'interview_id');
        $hasAppId       = Schema::hasColumn($e, 'application_id');
        $hasApplicantId = Schema::hasColumn($e, 'applicant_id');

        if ($hasInterviewId) {
            $q->leftJoin("$e as e", "e.interview_id", "=", "{$aliasInterview}.id");
            return;
        }

        if ($hasAppId) {
            $q->leftJoin("$e as e", "e.application_id", "=", "{$aliasApp}.id");
            return;
        }

        if ($hasApplicantId) {
            $q->leftJoin("$e as e", "e.applicant_id", "=", "{$aliasApp}.applicant_id");
            return;
        }
    }

    /**
     * ✅ Find applicant's User model from applicant_id (schema-safe).
     * Priority:
     * 1) applicants.user_id
     * 2) users.applicant_id
     * 3) users.email (via applicants.email)
     */
    protected function findApplicantUser(?int $applicantId): ?User
    {
        if (!$applicantId) return null;

        $userId = null;

        if (Schema::hasTable('applicants')) {
            if (Schema::hasColumn('applicants', 'user_id')) {
                $userId = DB::table('applicants')->where('id', $applicantId)->value('user_id');
            }

            if (
                !$userId &&
                Schema::hasColumn('applicants', 'email') &&
                Schema::hasTable('users') &&
                Schema::hasColumn('users', 'email')
            ) {
                $email = DB::table('applicants')->where('id', $applicantId)->value('email');
                if ($email) {
                    $userId = DB::table('users')->where('email', $email)->value('id');
                }
            }
        }

        if (
            !$userId &&
            Schema::hasTable('users') &&
            Schema::hasColumn('users', 'applicant_id')
        ) {
            $userId = DB::table('users')->where('applicant_id', $applicantId)->value('id');
        }

        return $userId ? User::find($userId) : null;
    }

    /**
     * ✅ Notify applicant using Laravel notifications (database channel).
     * This should appear in your NotificationController feed().
     */
    protected function notifyApplicantInterviewOutcome(array $payload): void
    {
        try {
            $u = $this->findApplicantUser($payload['applicant_id'] ?? null);
            if (!$u) {
                Log::warning('InterviewOutcome notify: applicant user not found', [
                    'applicant_id' => $payload['applicant_id'] ?? null,
                ]);
                return;
            }

            $u->notify(new InterviewOutcome(
                interviewId: $payload['interview_id'] ?? null,
                applicationId: $payload['application_id'] ?? null,
                vacancyId: $payload['vacancy_id'] ?? null,
                vacancyTitle: (string)($payload['vacancy_title'] ?? 'Vacancy'),
                outcome: (string)($payload['outcome'] ?? 'info'),
                scheduledAt: $payload['scheduled_at'] ?? null,
                mode: $payload['mode'] ?? null,
                location: $payload['location'] ?? null,
                meetingLink: $payload['meeting_link'] ?? null,
                extraInfo: $payload['extra_info'] ?? null,
                celebrate: (bool)($payload['celebrate'] ?? false)
            ));
        } catch (\Throwable $e) {
            Log::error('InterviewOutcome notify failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
        }
    }

    /**
     * ✅ Notify applicant (invited/scheduled) using InterviewInvited (database + mail).
     */
    protected function notifyApplicantInterviewInvite(array $payload): void
    {
        try {
            $u = $this->findApplicantUser($payload['applicant_id'] ?? null);
            if (!$u) {
                Log::warning('InterviewInvited notify: applicant user not found', [
                    'applicant_id' => $payload['applicant_id'] ?? null,
                ]);
                return;
            }

            $scheduledRaw = $payload['scheduled_at'] ?? null;

            // Ensure ISO string
            $scheduledIso = null;
            if ($scheduledRaw) {
                try {
                    $scheduledIso = Carbon::parse($scheduledRaw)->toIso8601String();
                } catch (\Throwable $e) {
                    $scheduledIso = (string) $scheduledRaw;
                }
            } else {
                $scheduledIso = now()->toIso8601String();
            }

            $u->notify(new InterviewInvited(
                interviewId: (int)($payload['interview_id'] ?? 0),
                vacancyId: (int)($payload['vacancy_id'] ?? 0),
                vacancyTitle: (string)($payload['vacancy_title'] ?? 'Vacancy'),
                scheduledAtIso: $scheduledIso,
                mode: (string)($payload['mode'] ?? 'unspecified'),
                location: $payload['location'] ?? null,
                meetingLink: $payload['meeting_link'] ?? null,
            ));
        } catch (\Throwable $e) {
            Log::error('InterviewInvited notify failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
        }
    }

    /** Hub: shortlisted + existing interviews */
    public function index(Request $req): Response
    {
        $ta = 'applications';
        $tv = 'vacancies';
        $ti = 'interviews';
        $tp = 'applicants';
        $tr = 'resumes';

        $a_id     = $this->col($ta, 'id') ?? 'id';
        $a_app    = $this->col($ta, 'applicant_id') ?? 'applicant_id';
        $a_vac    = $this->col($ta, 'vacancy_id', 'job_id') ?? 'vacancy_id';
        $a_score  = $this->col($ta, 'match_score', 'score') ?? 'match_score';
        $a_status = $this->col($ta, 'status', 'state') ?? 'status';
        $a_resume = $this->col($ta, 'resume_id');

        $p_name  = Schema::hasTable($tp) ? $this->col($tp, 'name', 'full_name') : null;
        $p_first = Schema::hasTable($tp) ? $this->col($tp, 'first_name', 'firstname') : null;
        $p_last  = Schema::hasTable($tp) ? $this->col($tp, 'last_name', 'lastname') : null;
        $p_email = Schema::hasTable($tp) ? $this->col($tp, 'email') : null;

        $r_path = Schema::hasTable($tr) ? $this->col($tr, 'file_path', 'path', 'stored_path', 'resume_path', 'url') : null;
        $r_name = Schema::hasTable($tr) ? $this->col($tr, 'original_name', 'filename', 'name', 'file_name') : null;

        $resumeHasAppId = Schema::hasTable($tr) && Schema::hasColumn($tr, 'application_id');

        // Shortlisted
        $shortlistedQ = DB::table("$ta as a")
            ->join("$tv as v", "v.id", "=", "a.$a_vac")
            ->leftJoin("$ti as i", "i.application_id", "=", "a.$a_id")
            ->where("a.$a_status", 'shortlisted')
            ->orderByDesc("a.$a_score");

        if (Schema::hasTable($tp)) $shortlistedQ->leftJoin("$tp as p", "p.id", "=", "a.$a_app");

        if (Schema::hasTable($tr)) {
            if ($a_resume && $resumeHasAppId) {
                $shortlistedQ->leftJoin("$tr as r", function ($join) use ($a_resume) {
                    $join->on("r.id", "=", "a.$a_resume")
                        ->orOn("r.application_id", "=", "a.id");
                });
            } elseif ($a_resume) {
                $shortlistedQ->leftJoin("$tr as r", "r.id", "=", "a.$a_resume");
            } elseif ($resumeHasAppId) {
                $shortlistedQ->leftJoin("$tr as r", "r.application_id", "=", "a.id");
            }
        }

        $shortlisted = $shortlistedQ
            ->get(array_filter([
                DB::raw("a.$a_id as application_id"),
                DB::raw("a.$a_app as applicant_id"),
                DB::raw("a.$a_score as match_score"),
                DB::raw("a.$a_status as application_status"),
                DB::raw("v.id as vacancy_id"),
                DB::raw("v.title as vacancy_title"),
                DB::raw("CASE WHEN i.id IS NULL THEN false ELSE true END as invited"),

                ($p_name ? DB::raw("p.$p_name as applicant_name") : null),
                (!$p_name && $p_first && $p_last ? DB::raw("concat_ws(' ', p.$p_first, p.$p_last) as applicant_name") : null),
                ($p_email ? DB::raw("p.$p_email as applicant_email") : null),

                ($a_resume ? DB::raw("a.$a_resume as resume_id") : null),
                ($r_path ? DB::raw("r.$r_path as resume_path") : null),
                ($r_name ? DB::raw("r.$r_name as resume_name") : null),
            ]))
            ->map(function ($r) {
                $r->anon_name  = $this->anon((int)($r->applicant_id ?? 0));
                $r->resume_url = $this->buildPublicUrl($r->resume_path ?? null);
                return $r;
            });

        // Existing interviews
        $interviewsQ = DB::table("$ti as i")
            ->join("$ta as a", "a.$a_id", "=", "i.application_id")
            ->join("$tv as v", "v.id", "=", "a.$a_vac")
            ->orderByDesc("i.scheduled_at")
            ->orderByDesc("i.id");

        if (Schema::hasTable($tp)) $interviewsQ->leftJoin("$tp as p", "p.id", "=", "a.$a_app");

        if (Schema::hasTable($tr)) {
            if ($a_resume && $resumeHasAppId) {
                $interviewsQ->leftJoin("$tr as r", function ($join) use ($a_resume) {
                    $join->on("r.id", "=", "a.$a_resume")
                        ->orOn("r.application_id", "=", "a.id");
                });
            } elseif ($a_resume) {
                $interviewsQ->leftJoin("$tr as r", "r.id", "=", "a.$a_resume");
            } elseif ($resumeHasAppId) {
                $interviewsQ->leftJoin("$tr as r", "r.application_id", "=", "a.id");
            }
        }

        $this->applyEmployeeJoin($interviewsQ, 'i', 'a');

        $interviews = $interviewsQ
            ->get(array_filter([
                'i.id',
                'i.application_id',
                'i.scheduled_at',
                'i.panel_count',
                'i.interview_score',
                'i.final_score',
                'i.status',
                'i.mode',
                'i.location',
                'i.meeting_link',
                'i.extra_info',
                'i.candidate_status',
                'i.candidate_reason',

                DB::raw("a.$a_app as applicant_id"),
                DB::raw("a.$a_score as match_score"),
                DB::raw("a.$a_status as application_status"),
                DB::raw("v.id as vacancy_id"),
                DB::raw("v.title as vacancy_title"),

                ($p_name ? DB::raw("p.$p_name as applicant_name") : null),
                (!$p_name && $p_first && $p_last ? DB::raw("concat_ws(' ', p.$p_first, p.$p_last) as applicant_name") : null),
                ($p_email ? DB::raw("p.$p_email as applicant_email") : null),

                ($a_resume ? DB::raw("a.$a_resume as resume_id") : null),
                ($r_path ? DB::raw("r.$r_path as resume_path") : null),
                ($r_name ? DB::raw("r.$r_name as resume_name") : null),

                (Schema::hasTable('employees') ? DB::raw("CASE WHEN e.id IS NULL THEN false ELSE true END as is_employee") : null),
                (Schema::hasTable('employees') ? DB::raw("e.id as employee_id") : null),
                (Schema::hasTable('employees') && Schema::hasColumn('employees', 'employee_no') ? DB::raw("e.employee_no as employee_no") : null),
            ]))
            ->map(function ($r) {
                $r->anon_name  = $this->anon((int)($r->applicant_id ?? 0));
                $r->resume_url = $this->buildPublicUrl($r->resume_path ?? null);
                return $r;
            });

        return Inertia::render('HR/Interviews/Index', [
            'shortlisted' => $shortlisted,
            'interviews'  => $interviews,
            'weights'     => ['resume' => 0.6, 'interview' => 0.4],
        ]);
    }

    /** Per-vacancy evaluation board */
    public function vacancy(int $id): Response
    {
        $vacancy = DB::table('vacancies')->where('id', $id)->first();
        abort_unless($vacancy, 404, 'Vacancy not found.');

        $a = 'applications';
        $i = 'interviews';
        $p = 'applicants';
        $r = 'resumes';

        $a_resume = $this->col($a, 'resume_id');
        $p_name   = Schema::hasTable($p) ? $this->col($p, 'name', 'full_name') : null;
        $p_first  = Schema::hasTable($p) ? $this->col($p, 'first_name', 'firstname') : null;
        $p_last   = Schema::hasTable($p) ? $this->col($p, 'last_name', 'lastname') : null;
        $p_email  = Schema::hasTable($p) ? $this->col($p, 'email') : null;

        $r_path   = Schema::hasTable($r) ? $this->col($r, 'file_path', 'path', 'stored_path', 'resume_path', 'url') : null;
        $r_name   = Schema::hasTable($r) ? $this->col($r, 'original_name', 'filename', 'name', 'file_name') : null;
        $resumeHasAppId = Schema::hasTable($r) && Schema::hasColumn($r, 'application_id');

        $q = DB::table("$i as i")
            ->join("$a as a", 'a.id', '=', 'i.application_id')
            ->where('a.vacancy_id', $id)
            ->orderByDesc('i.scheduled_at')
            ->orderByDesc('i.id');

        if (Schema::hasTable($p)) $q->leftJoin("$p as p", 'p.id', '=', 'a.applicant_id');

        if (Schema::hasTable($r)) {
            if ($a_resume && $resumeHasAppId) {
                $q->leftJoin("$r as r", function ($join) use ($a_resume) {
                    $join->on("r.id", "=", "a.$a_resume")
                        ->orOn("r.application_id", "=", "a.id");
                });
            } elseif ($a_resume) {
                $q->leftJoin("$r as r", "r.id", "=", "a.$a_resume");
            } elseif ($resumeHasAppId) {
                $q->leftJoin("$r as r", "r.application_id", "=", "a.id");
            }
        }

        $this->applyEmployeeJoin($q, 'i', 'a');

        $interviews = $q->get(array_filter([
            'i.id',
            'i.application_id',
            'i.scheduled_at',
            'i.panel_count',
            'i.interview_score',
            'i.final_score',
            'i.status',
            'i.mode',
            'i.location',
            'i.meeting_link',
            'i.extra_info',
            'i.candidate_status',
            'i.candidate_reason',

            DB::raw('a.applicant_id'),
            DB::raw('coalesce(a.match_score,0) as resume_score'),

            ($p_name ? DB::raw("p.$p_name as applicant_name") : null),
            (!$p_name && $p_first && $p_last ? DB::raw("concat_ws(' ', p.$p_first, p.$p_last) as applicant_name") : null),
            ($p_email ? DB::raw("p.$p_email as applicant_email") : null),

            ($a_resume ? DB::raw("a.$a_resume as resume_id") : null),
            ($r_path ? DB::raw("r.$r_path as resume_path") : null),
            ($r_name ? DB::raw("r.$r_name as resume_name") : null),

            (Schema::hasTable('employees') ? DB::raw("CASE WHEN e.id IS NULL THEN false ELSE true END as is_employee") : null),
            (Schema::hasTable('employees') ? DB::raw("e.id as employee_id") : null),
            (Schema::hasTable('employees') && Schema::hasColumn('employees', 'employee_no') ? DB::raw("e.employee_no as employee_no") : null),
        ]))
        ->map(function ($row) {
            $row->anon_name  = $this->anon((int)($row->applicant_id ?? 0));
            $row->resume_url = $this->buildPublicUrl($row->resume_path ?? null);
            return $row;
        });

        $ids = $interviews->pluck('id')->all();
        $panels = collect();

        if (!empty($ids) && Schema::hasTable('interview_panels')) {
            $panelCol = $this->panelNoColumn();
            $rows = DB::table('interview_panels')
                ->whereIn('interview_id', $ids)
                ->orderBy('interview_id')
                ->orderBy($panelCol, 'asc')
                ->get();

            $panels = $rows->groupBy('interview_id')->map->values();
        }

        return Inertia::render('HR/Interviews/Evaluation', [
            'vacancy'    => $vacancy,
            'interviews' => $interviews,
            'panels'     => $panels,
            'weights'    => ['resume' => 0.6, 'interview' => 0.4],
        ]);
    }

    /** Update interview details */
    public function updateDetails(int $id, Request $req)
    {
        $i = DB::table('interviews')->where('id', $id)->first();
        abort_unless($i, 404, 'Interview not found.');

        $data = $req->validate([
            'scheduled_at' => 'nullable',
            'mode'         => 'nullable|string|max:16',
            'location'     => 'nullable|string|max:500',
            'meeting_link' => 'nullable|string|max:500',
            'extra_info'   => 'nullable|string|max:4000',
        ]);

        $update = ['updated_at' => now()];

        foreach (['mode', 'location', 'meeting_link', 'extra_info'] as $k) {
            if (array_key_exists($k, $data)) $update[$k] = $data[$k];
        }

        if (array_key_exists('scheduled_at', $data)) {
            $update['scheduled_at'] = $data['scheduled_at'] ?: null;

            $st = $this->normalizeInterviewStatus((string)($i->status ?? ''));
            if (!in_array($st, ['done', 'scored', 'cancelled', 'no_show'], true)) {
                $update['status'] = $data['scheduled_at'] ? 'scheduled' : ($i->status ?? 'invited');
            }
        }

        DB::table('interviews')->where('id', $id)->update($update);

        return back()->with('status', 'Interview details updated.');
    }

    /** Invite / upsert interview row */
    public function upsert(Request $req)
    {
        $appId = (int)$req->input('application_id');
        abort_if(!$appId, 422, 'application_id is required');

        $app = DB::table('applications')->where('id', $appId)->first();
        abort_unless($app, 404, 'Application not found.');

        $vacancyTitle = null;
        if (!empty($app->vacancy_id) && Schema::hasTable('vacancies')) {
            $vacancyTitle = DB::table('vacancies')->where('id', $app->vacancy_id)->value('title');
        }

        $now = now();
        $hrUserId = Auth::id();

        // ✅ Cancel interview
        if ($req->boolean('cancel')) {
            $existing = DB::table('interviews')->where('application_id', $appId)->first();

            if ($existing) {
                $update = [
                    'status'           => 'cancelled',
                    'candidate_status' => 'cancelled_by_hr',
                    'updated_at'       => $now,
                ];

                if (Schema::hasColumn('interviews', 'scheduled_at')) {
                    $update['scheduled_at'] = null;
                }

                DB::table('interviews')->where('id', $existing->id)->update($update);

                // cancel -> keep InterviewOutcome (database)
                $this->notifyApplicantInterviewOutcome([
                    'outcome'        => 'cancelled',
                    'interview_id'   => (int)$existing->id,
                    'application_id' => (int)$appId,
                    'applicant_id'   => (int)($app->applicant_id ?? 0),
                    'vacancy_id'     => (int)($app->vacancy_id ?? 0),
                    'vacancy_title'  => $vacancyTitle ?: 'Vacancy',
                    'scheduled_at'   => null,
                    'celebrate'      => false,
                ]);
            }

            return back()->with('status', 'Interview cancelled.');
        }

        // ✅ Create / update interview
        $payload = [
            'applicant_id' => $app->applicant_id ?? null,
            'vacancy_id'   => $app->vacancy_id ?? null,
            'mode'         => (string)$req->input('mode', 'unspecified'),
            'location'     => $req->input('location'),
            'meeting_link' => $req->input('meeting_link'),
            'extra_info'   => $req->input('extra_info'),
            'updated_at'   => $now,
        ];

        if (Schema::hasColumn('interviews', 'created_by') && $hrUserId) {
            $payload['created_by'] = $hrUserId;
        }

        $isScheduled = false;
        if ($req->filled('scheduled_at')) {
            $payload['scheduled_at'] = $req->input('scheduled_at');
            $payload['status'] = 'scheduled';
            $isScheduled = true;
        } else {
            $payload['status'] = 'invited';
        }

        $existing = DB::table('interviews')->where('application_id', $appId)->first();
        $interviewId = null;

        if ($existing) {
            DB::table('interviews')->where('id', $existing->id)->update($payload);
            $interviewId = (int)$existing->id;
        } else {
            $payload['application_id'] = $appId;
            $payload['created_at'] = $now;
            $payload['candidate_status'] = 'pending';

            $interviewId = (int)DB::table('interviews')->insertGetId($payload);
        }

        // ✅ invited/scheduled -> InterviewInvited (database + email)
        $this->notifyApplicantInterviewInvite([
            'interview_id'   => $interviewId,
            'application_id' => (int)$appId,
            'applicant_id'   => (int)($app->applicant_id ?? 0),
            'vacancy_id'     => (int)($app->vacancy_id ?? 0),
            'vacancy_title'  => $vacancyTitle ?: 'Vacancy',
            'scheduled_at'   => $payload['scheduled_at'] ?? null,
            'mode'           => $payload['mode'] ?? null,
            'location'       => $payload['location'] ?? null,
            'meeting_link'   => $payload['meeting_link'] ?? null,
        ]);

        return back()->with('status', 'Interview saved.');
    }

    /** PATCH /hr/interviews/{id}/status */
    public function status(int $id, Request $req)
    {
        $i = DB::table('interviews')->where('id', $id)->first();
        abort_unless($i, 404, 'Interview not found.');

        $data = $req->validate([
            'status' => 'required|string|max:16',
        ]);

        $status  = $this->normalizeInterviewStatus($data['status']);
        $allowed = $this->allowedInterviewStatuses();

        abort_unless(in_array($status, $allowed, true), 422, 'Invalid status');

        DB::table('interviews')->where('id', $id)->update([
            'status'     => $status,
            'updated_at' => now(),
        ]);

        return back()->with('status', "Interview updated → {$status}");
    }

    /** Compute interview_score and final_score */
    public function finalize(int $id, Request $req)
    {
        $weights = ['resume' => 0.6, 'interview' => 0.4];

        $i = DB::table('interviews')->where('id', $id)->first();
        abort_unless($i, 404, 'Interview not found.');

        $this->ensureEvaluationUnlocked($i);

        $scoreCol = $this->panelScoreColumn();

        $scores = DB::table('interview_panels')
            ->where('interview_id', $id)
            ->pluck($scoreCol)
            ->all();

        $scores = array_values(array_filter($scores, fn ($x) => is_numeric($x) && (int)$x >= 1 && (int)$x <= 5));
        $avg = count($scores) ? array_sum($scores) / count($scores) : 0;

        $interviewScore = (int)round(($avg / 5) * 100);

        $app = DB::table('applications')->where('id', $i->application_id)->first();
        $resumeScore = (float)($app->match_score ?? 0);

        $final = (int)round($resumeScore * $weights['resume'] + $interviewScore * $weights['interview']);

        DB::table('interviews')->where('id', $id)->update([
            'panel_count'     => count($scores),
            'interview_score' => $interviewScore,
            'final_score'     => $final,
            'status'          => 'scored',
            'updated_at'      => now(),
        ]);

        return back()->with('status', 'Interview score finalized.');
    }

    /** Create / remove panel rows */
    public function panels(int $id, Request $req)
    {
        $count = max(0, min(20, (int)$req->input('panel_count', 0)));

        $interview = DB::table('interviews')->where('id', $id)->first();
        abort_unless($interview, 404, 'Interview not found.');

        $this->ensureEvaluationUnlocked($interview);

        $panelCol = $this->panelNoColumn();

        DB::transaction(function () use ($id, $count, $panelCol) {
            $current = DB::table('interview_panels')
                ->where('interview_id', $id)
                ->pluck($panelCol)
                ->all();

            $have = collect($current);
            $want = collect(range(1, $count));

            $toAdd = $want->diff($have)->values()->all();
            $toDel = $have->diff($want)->values()->all();

            if ($toAdd) {
                $now = now();
                $rows = array_map(function ($n) use ($id, $panelCol, $now) {
                    $base = [
                        'interview_id' => $id,
                        $panelCol      => $n,
                        'created_at'   => $now,
                        'updated_at'   => $now,
                    ];
                    return $base + $this->panelDefaults((int)$n);
                }, $toAdd);

                DB::table('interview_panels')->insert($rows);
            }

            if ($toDel) {
                DB::table('interview_panels')
                    ->where('interview_id', $id)
                    ->whereIn($panelCol, $toDel)
                    ->delete();
            }

            DB::table('interviews')->where('id', $id)->update([
                'panel_count' => $count,
                'updated_at'  => now(),
            ]);
        });

        return back()->with('status', 'Panels updated.');
    }

    /** Save rating */
    public function rate(int $id, Request $req)
    {
        $panelNo = (int)$req->input('panel_no');
        $rating  = $req->input('rating');

        abort_if($panelNo < 1 || $panelNo > 20, 422, 'Invalid panel_no.');

        if (!is_null($rating)) {
            $rating = (int)$rating;
            abort_if($rating < 1 || $rating > 5, 422, 'Invalid rating (1..5).');
        }

        $interview = DB::table('interviews')->where('id', $id)->first();
        abort_unless($interview, 404, 'Interview not found.');

        $this->ensureEvaluationUnlocked($interview);

        $panelCol = $this->panelNoColumn();
        $scoreCol = $this->panelScoreColumn();
        $now = now();

        $exists = DB::table('interview_panels')
            ->where('interview_id', $id)
            ->where($panelCol, $panelNo)
            ->exists();

        if (!$exists) {
            $row = [
                'interview_id' => $id,
                $panelCol      => $panelNo,
                'created_at'   => $now,
                'updated_at'   => $now,
            ] + $this->panelDefaults($panelNo);

            DB::table('interview_panels')->insert($row);
        }

        $update = ['updated_at' => $now];

        if ($req->has('name') && Schema::hasColumn('interview_panels', 'name')) {
            $nm = trim((string)$req->input('name'));
            $update['name'] = $nm !== '' ? $nm : "Panel {$panelNo}";
        }

        // store rating/stars
        if ($scoreCol === 'rating' && Schema::hasColumn('interview_panels', 'rating')) {
            $update['rating'] = $rating;
        }
        if ($scoreCol === 'stars' && Schema::hasColumn('interview_panels', 'stars')) {
            $update['stars'] = $rating ?? 0;
        }

        if ($req->has('notes') && Schema::hasColumn('interview_panels', 'notes')) {
            $update['notes'] = $req->input('notes');
        }
        if ($req->has('comment') && Schema::hasColumn('interview_panels', 'comment')) {
            $update['comment'] = $req->input('comment');
        }

        DB::table('interview_panels')
            ->where('interview_id', $id)
            ->where($panelCol, $panelNo)
            ->update($update);

        return back()->with('status', 'Rating saved.');
    }

    /**
     * Convert scored candidate into employee
     * POST /hr/interviews/{id}/hire
     */
    public function hire(int $id, Request $req)
    {
        $interview = DB::table('interviews')->where('id', $id)->first();
        abort_unless($interview, 404, 'Interview not found.');

        $st = $this->normalizeInterviewStatus((string)($interview->status ?? ''));
        abort_unless($st === 'scored', 403, 'Please Finalize score first (status must be SCORED).');

        abort_unless(Schema::hasTable('employees'), 500, 'employees table not found. Please create employees table migration.');

        $app = DB::table('applications')->where('id', $interview->application_id)->first();
        abort_unless($app, 404, 'Application not found.');

        $applicant = null;
        if (Schema::hasTable('applicants') && isset($app->applicant_id)) {
            $applicant = DB::table('applicants')->where('id', $app->applicant_id)->first();
        }

        $vacancy = null;
        if (Schema::hasTable('vacancies') && isset($app->vacancy_id)) {
            $vacancy = DB::table('vacancies')->where('id', $app->vacancy_id)->first();
        }

        $te = 'employees';

        $e_empNo      = $this->col($te, 'employee_no', 'staff_no', 'emp_no');
        $e_name       = $this->col($te, 'name', 'full_name');
        $e_email      = $this->col($te, 'email');
        $e_position   = $this->col($te, 'position', 'job_title', 'title');
        $e_department = $this->col($te, 'department', 'dept');
        $e_status     = $this->col($te, 'status');
        $e_hiredAt    = $this->col($te, 'hired_at', 'hire_date');

        $e_applicantId   = $this->col($te, 'applicant_id');
        $e_vacancyId     = $this->col($te, 'vacancy_id');
        $e_applicationId = $this->col($te, 'application_id');
        $e_interviewId   = $this->col($te, 'interview_id');

        $hasCreatedAt = Schema::hasColumn($te, 'created_at');
        $hasUpdatedAt = Schema::hasColumn($te, 'updated_at');

        // prevent duplicates
        $existingQ = DB::table($te);

        if ($e_interviewId) {
            $existingQ->where($e_interviewId, $id);
        } else {
            if ($e_applicationId) $existingQ->where($e_applicationId, $interview->application_id);
        }

        $existing = $existingQ->first();
        if ($existing) {
            return redirect('/hr/employees')->with('status', 'Employee already exists (no duplicate created).');
        }

        $now = now();

        $candidateName = null;
        $candidateEmail = null;

        if ($applicant) {
            if (isset($applicant->name)) $candidateName = $applicant->name;
            elseif (isset($applicant->full_name)) $candidateName = $applicant->full_name;
            elseif (isset($applicant->first_name) || isset($applicant->last_name)) {
                $candidateName = trim(($applicant->first_name ?? '') . ' ' . ($applicant->last_name ?? ''));
            }
            if (isset($applicant->email)) $candidateEmail = $applicant->email;
        }

        $position = $vacancy->title ?? null;

        $generatedEmpNo = null;
        if ($e_empNo) {
            $generatedEmpNo = 'EMP' . $now->format('Ymd') . '-' . strtoupper(substr(sha1(($app->applicant_id ?? 0) . '|' . $now->timestamp), 0, 4));
        }

        $payload = [];

        if ($e_empNo)      $payload[$e_empNo] = $generatedEmpNo;
        if ($e_name)       $payload[$e_name] = $candidateName ?? ('Employee #' . ($app->applicant_id ?? $interview->application_id));
        if ($e_email)      $payload[$e_email] = $candidateEmail;
        if ($e_position)   $payload[$e_position] = $position;
        if ($e_department && isset($vacancy->department)) $payload[$e_department] = $vacancy->department;

        if ($e_status)     $payload[$e_status] = 'active';
        if ($e_hiredAt)    $payload[$e_hiredAt] = $now;

        if ($e_applicantId)   $payload[$e_applicantId] = $app->applicant_id ?? null;
        if ($e_vacancyId)     $payload[$e_vacancyId] = $app->vacancy_id ?? null;
        if ($e_applicationId) $payload[$e_applicationId] = $interview->application_id;
        if ($e_interviewId)   $payload[$e_interviewId] = $id;

        if ($hasCreatedAt) $payload['created_at'] = $now;
        if ($hasUpdatedAt) $payload['updated_at'] = $now;

        DB::table('employees')->insert($payload);

        // update interview + application
        $intUpd = ['updated_at' => $now];
        if (Schema::hasColumn('interviews', 'candidate_status')) $intUpd['candidate_status'] = 'hired';
        if (Schema::hasColumn('interviews', 'candidate_reason')) $intUpd['candidate_reason'] = 'converted_to_employee';
        DB::table('interviews')->where('id', $id)->update($intUpd);

        if (Schema::hasColumn('applications', 'status')) {
            try {
                DB::table('applications')->where('id', $interview->application_id)->update([
                    'status' => 'hired',
                    'updated_at' => $now,
                ]);
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // ✅ Notify applicant: hired (database only via InterviewOutcome)
        $this->notifyApplicantInterviewOutcome([
            'outcome'        => 'hired',
            'interview_id'   => (int)$id,
            'application_id' => (int)($interview->application_id ?? 0),
            'applicant_id'   => (int)($app->applicant_id ?? 0),
            'vacancy_id'     => (int)($app->vacancy_id ?? 0),
            'vacancy_title'  => (string)($vacancy->title ?? 'Vacancy'),
            'scheduled_at'   => $interview->scheduled_at ?? null,
            'celebrate'      => true,
        ]);

        return redirect('/hr/employees')->with('status', 'Candidate converted to employee successfully.');
    }
}

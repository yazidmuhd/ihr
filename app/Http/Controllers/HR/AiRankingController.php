<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Services\MatchScorer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

// ✅ Notifications
use App\Models\User;
use App\Notifications\ApplicationStatusChanged;

class AiRankingController extends Controller
{
    /**
     * Compute and persist score+breakdown for an application.
     */
    protected function scoreOne(object $vacancy, object $app): ?array
    {
        if (empty($app->resume_id)) {
            return null;
        }

        // Fetch latest parsed resume record (your current design)
        $parsedRecord = DB::table('parsed_resumes')
            ->where('resume_id', $app->resume_id)
            ->orderByDesc('id')
            ->first();

        if (!$parsedRecord || empty($parsedRecord->entities)) {
            return null;
        }

        $parsedEntities = json_decode($parsedRecord->entities, true);
        if (!is_array($parsedEntities)) {
            $parsedEntities = [];
        }

        $vacPayload = [
            'title'       => $vacancy->title ?? '',
            'description' => $vacancy->description ?? '',
        ];

        $S = MatchScorer::score($parsedEntities, $vacPayload);

        DB::table('applications')
            ->where('id', $app->id)
            ->update([
                'match_score'     => (int) ($S['score'] ?? 0),
                'match_breakdown' => json_encode($S['breakdown'] ?? []),
                'updated_at'      => now(),
            ]);

        return $S;
    }

    /**
     * Display the Ranking Board
     * Route: GET /hr/vacancies/{id}/ai
     */
    public function index(Request $req, int $id): Response
    {
        $vacancy = DB::table('vacancies')->where('id', $id)->first();
        abort_if(!$vacancy, 404, 'Vacancy not found.');

        $apps = DB::table('applications')
            ->where('vacancy_id', $id)
            ->orderByDesc('match_score')
            ->orderBy('id', 'asc')
            ->get([
                'id',
                'applicant_id',
                'resume_id',
                'status',
                'match_score',
                'match_breakdown',
                DB::raw("to_char(created_at, 'YYYY-MM-DD HH24:MI:SS') as created_at"),
            ]);

        $rows = [];

        foreach ($apps as $a) {
            $bd = $a->match_breakdown;

            // If breakdown missing, try scoring now
            if (is_null($bd) || $bd === '' || $bd === 'null') {
                $S  = $this->scoreOne($vacancy, $a);
                $bd = $S['breakdown'] ?? [];
            } else {
                if (is_string($bd)) {
                    $bd = json_decode($bd, true) ?: [];
                } elseif (!is_array($bd)) {
                    $bd = [];
                }
            }

            $rows[] = [
                'id'           => $a->id,
                'applicant_id' => $a->applicant_id,
                'name'         => 'Candidate #' . ($a->applicant_id ?? '—'),

                'score' => (int) ($a->match_score ?? 0),

                'skills_matched' => $bd['skills']['matched'] ?? ($bd['overlap_skills'] ?? []),
                'skills_missing' => $bd['skills']['missing'] ?? [],

                'experience' => [
                    'required'  => $bd['experience']['required'] ?? 0,
                    'candidate' => $bd['experience']['candidate'] ?? ($bd['experience_years'] ?? 0),
                ],
                'education' => [
                    'required'  => $bd['education']['required'] ?? '-',
                    'candidate' => $bd['education']['candidate'] ?? ($bd['education'] ?? '-'),
                ],

                'status'     => $a->status,
                'created_at' => $a->created_at,
            ];
        }

        return Inertia::render('HR/Vacancies/AIRanking', [
            'vacancy' => [
                'id'         => $vacancy->id,
                'title'      => $vacancy->title,
                'department' => $vacancy->department ?? null,
                'location'   => $vacancy->location ?? null,
            ],
            'rows' => $rows,
        ]);
    }

    /**
     * Force rescore all applicants for this vacancy
     * Route: POST /hr/vacancies/{id}/ai/rescore
     */
    public function rescoreAll(Request $req, int $id)
    {
        $vacancy = DB::table('vacancies')->where('id', $id)->first();
        abort_if(!$vacancy, 404, 'Vacancy not found.');

        $apps = DB::table('applications')
            ->where('vacancy_id', $id)
            ->get(['id', 'resume_id']);

        foreach ($apps as $a) {
            $this->scoreOne($vacancy, $a);
        }

        return back()->with('status', 'All applications rescored.');
    }

    /**
     * Alias for older routes that call "rerank"
     */
    public function rerank(Request $req, int $id)
    {
        return $this->rescoreAll($req, $id);
    }

    /**
     * Update application decision inside AI board
     * Route: POST /hr/vacancies/{id}/ai/shortlist
     * ✅ Now also notifies applicant if status changes (#5)
     */
    public function shortlist(Request $req, int $id)
    {
        $appId    = (int) $req->input('application_id');
        $decision = (string) $req->input('decision', 'shortlisted');
        $decision = $this->normalizeApplicationStatus($decision);

        $allowed = ['shortlisted', 'rejected', 'in_review', 'submitted', 'hired', 'withdrawn'];
        if (!in_array($decision, $allowed, true)) {
            return back()->with('status', "Invalid decision: {$decision}");
        }

        $appInfo = $this->loadApplicationInfo($appId);
        abort_if(!$appInfo, 404, 'Application not found.');

        $oldStatus = (string) ($appInfo->status ?? '');

        DB::table('applications')
            ->where('id', $appId)
            ->where('vacancy_id', $id)
            ->update([
                'status'     => $decision,
                'updated_at' => now(),
            ]);

        // ✅ Notify applicant if changed
        if ($decision !== $oldStatus) {
            $this->notifyApplicantStatusChanged($appInfo, $decision);
        }

        return back()->with('status', 'Application updated.');
    }

    /**
     * PATCH /hr/applications/{id}/status  ->  AiRankingController@setStatus
     * ✅ Now notifies applicant when status changes (#5)
     */
    public function setStatus(Request $request, int $id)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $data = $request->validate([
            'status' => 'required|string|max:50',
            'note'   => 'nullable|string|max:1000',
        ]);

        $status = $this->normalizeApplicationStatus($data['status']);

        $allowed = [
            'submitted',
            'in_review',
            'shortlisted',
            'rejected',
            'hired',
            'interview_invited',
            'interview_scheduled',
            'withdrawn',
        ];

        if (!in_array($status, $allowed, true)) {
            return back()->with('status', "Invalid status: {$status}");
        }

        $appInfo = $this->loadApplicationInfo($id);
        abort_if(!$appInfo, 404, 'Application not found.');

        $oldStatus = (string) ($appInfo->status ?? '');

        $update = [
            'status'     => $status,
            'updated_at' => now(),
        ];

        // Optional note columns (only update if they exist)
        if (!empty($data['note'])) {
            if (Schema::hasColumn('applications', 'hr_note')) {
                $update['hr_note'] = $data['note'];
            } elseif (Schema::hasColumn('applications', 'status_note')) {
                $update['status_note'] = $data['note'];
            } elseif (Schema::hasColumn('applications', 'remarks')) {
                $update['remarks'] = $data['note'];
            }
        }

        // Optional: track who updated (if your schema supports)
        if (Schema::hasColumn('applications', 'updated_by')) {
            $update['updated_by'] = $user->id;
        } elseif (Schema::hasColumn('applications', 'hr_user_id')) {
            $update['hr_user_id'] = $user->id;
        }

        DB::table('applications')->where('id', $id)->update($update);

        // ✅ Notify applicant if status changed
        if ($status !== $oldStatus) {
            $this->notifyApplicantStatusChanged($appInfo, $status);
        }

        return back()->with('status', "Application updated → {$status}");
    }

    /**
     * Load app + vacancy title + applicant.user_id (for notifications)
     */
    private function loadApplicationInfo(int $applicationId): ?object
    {
        if (!Schema::hasTable('applications')) return null;
        if (!Schema::hasTable('vacancies')) return null;

        $q = DB::table('applications as a')
            ->join('vacancies as v', 'v.id', '=', 'a.vacancy_id')
            ->leftJoin('applicants as ap', 'ap.id', '=', 'a.applicant_id')
            ->where('a.id', $applicationId)
            ->select([
                'a.id',
                'a.status',
                'a.applicant_id',
                'a.vacancy_id',
                'v.title as vacancy_title',
                'ap.user_id as applicant_user_id',
                'ap.email as applicant_email',
            ]);

        return $q->first();
    }

    /**
     * ✅ Send ApplicationStatusChanged notification (#5) to the applicant user.
     * Uses applicants.user_id first, then falls back to users.applicant_id or email if needed.
     */
    private function notifyApplicantStatusChanged(object $appInfo, string $newStatus): void
    {
        try {
            $userId = $appInfo->applicant_user_id ?? null;

            // Fallback 1: users.applicant_id
            if (!$userId && Schema::hasColumn('users', 'applicant_id') && !empty($appInfo->applicant_id)) {
                $userId = DB::table('users')->where('applicant_id', $appInfo->applicant_id)->value('id');
            }

            // Fallback 2: users.email
            if (!$userId && !empty($appInfo->applicant_email) && Schema::hasColumn('users', 'email')) {
                $userId = DB::table('users')->where('email', $appInfo->applicant_email)->value('id');
            }

            if (!$userId) return;

            $u = User::find($userId);
            if (!$u) return;

            $u->notify(new ApplicationStatusChanged(
                applicationId: (int) $appInfo->id,
                vacancyTitle: (string) ($appInfo->vacancy_title ?? 'Vacancy'),
                newStatus: $newStatus
            ));
        } catch (\Throwable $e) {
            // swallow: never break HR workflow due to notification
        }
    }

    /** Normalize common variations into consistent keys */
    private function normalizeApplicationStatus(string $value): string
    {
        $s = strtolower(trim($value));
        $s = str_replace(['-', ' '], '_', $s);

        $map = [
            'review'     => 'in_review',
            'inreview'   => 'in_review',

            'shortlist'  => 'shortlisted',
            'short_list' => 'shortlisted',

            'reject'     => 'rejected',
            'decline'    => 'rejected',

            'hire'       => 'hired',
            'accepted'   => 'hired',

            'invited'    => 'interview_invited',
            'interview'  => 'interview_invited',
            'scheduled'  => 'interview_scheduled',
        ];

        return $map[$s] ?? $s;
    }
}

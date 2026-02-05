<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Services\MatchScorer;
use App\Services\ResumeHeuristicsParser; // optional; guarded before use
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

// ✅ Notifications
use App\Models\User;
use App\Notifications\ApplicationSubmitted;

class JobBrowserController extends Controller
{
    /** Browse + filter open vacancies */
    public function index(Request $request): Response
    {
        $q    = trim((string) $request->get('q', ''));
        $dept = (string) $request->get('dept', 'All');
        $type = (string) $request->get('type', 'All');
        $loc  = (string) $request->get('loc', 'All');

        $query = DB::table('vacancies')
            ->select([
                'id',
                'title',
                'department',
                'location',
                'employment_type as type',
                'description',
                'status',
                DB::raw("to_char(closing_date, 'YYYY-MM-DD') as deadline"),
                DB::raw("to_char(created_at,  'YYYY-MM-DD') as created_at"),
            ])
            ->where('status', 'Open')
            ->when($q !== '', fn ($qq) => $qq->where(function ($w) use ($q) {
                $w->where('title', 'ilike', "%$q%")
                    ->orWhere('department', 'ilike', "%$q%")
                    ->orWhere('location', 'ilike', "%$q%")
                    ->orWhere('description', 'ilike', "%$q%");
            }))
            ->when($dept !== 'All', fn ($qq) => $qq->where('department', $dept))
            ->when($type !== 'All', fn ($qq) => $qq->where('employment_type', $type))
            ->when($loc  !== 'All', fn ($qq) => $qq->where('location', $loc))
            ->orderByDesc('id');

        $rows = $query->paginate(12)->withQueryString();

        $departments = DB::table('vacancies')->where('status', 'Open')
            ->whereNotNull('department')->select('department')->distinct()->orderBy('department')->pluck('department');

        $types = DB::table('vacancies')->where('status', 'Open')
            ->select('employment_type')->distinct()->orderBy('employment_type')->pluck('employment_type');

        $locations = DB::table('vacancies')->where('status', 'Open')
            ->whereNotNull('location')->select('location')->distinct()->orderBy('location')->pluck('location');

        return Inertia::render('Applicant/Jobs/Index', [
            'filters'     => ['q' => $q, 'dept' => $dept, 'type' => $type, 'loc' => $loc],
            'rows'        => $rows,
            'departments' => $departments,
            'types'       => $types,
            'locations'   => $locations,
        ]);
    }

    /** Vacancy details (+ show apply/withdraw state, active résumé flag) */
    public function show(int $id): Response
    {
        $v = DB::table('vacancies')
            ->select([
                'id',
                'title',
                'department',
                'location',
                'employment_type',
                'description',
                'status',
                DB::raw("to_char(closing_date, 'YYYY-MM-DD') as deadline"),
                DB::raw("to_char(created_at,  'YYYY-MM-DD') as created_at"),

                // structured AI fields
                'experience_min_years',
                'experience_max_years',
                'experience_years_required',
                'education_required',
                'skills_required',
            ])
            ->where('id', $id)
            ->first();

        abort_if(!$v, 404);

        // Decode skills_required if stored as JSON text
        if (isset($v->skills_required) && is_string($v->skills_required)) {
            $decoded = json_decode($v->skills_required, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $v->skills_required = $decoded;
            }
        }

        // ✅ use true so email-matched applicant gets linked to user_id
        $applicantId     = $this->resolveApplicantId(true);
        $already         = false;
        $myApp           = null;
        $hasActiveResume = false;

        if ($applicantId && Schema::hasTable('applications')) {
            $myApp = DB::table('applications')
                ->where('applicant_id', $applicantId)
                ->where('vacancy_id', $id)
                ->orderByDesc('id')
                ->first(['id', 'status']);

            $already = (bool) $myApp && $myApp->status !== 'withdrawn';
        }

        if ($applicantId && Schema::hasTable('resumes')) {
            $activeCol = Schema::hasColumn('resumes', 'is_active')
                ? 'is_active'
                : (Schema::hasColumn('resumes', 'active') ? 'active' : null);

            if ($activeCol) {
                $hasActiveResume = DB::table('resumes')
                    ->where('applicant_id', $applicantId)
                    ->where($activeCol, true)
                    ->exists();
            }
        }

        return Inertia::render('Applicant/Jobs/Show', [
            'v'               => $v,
            'alreadyApplied'  => $already,
            'myApplication'   => $myApp,
            'hasActiveResume' => $hasActiveResume,
        ]);
    }

    /** Apply to job: ensure applicant exists, ensure active parsed resume, then score */
    public function apply(Request $request)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $data = $request->validate([
            'vacancy_id' => 'required|integer',
        ]);
        $vacancyId = (int) $data['vacancy_id'];

        // ✅ Ensure applicant profile exists (AUTO-CREATE / AUTO-LINK IF MISSING)
        $applicantId = $this->resolveApplicantId(true);
        if (!$applicantId) {
            return redirect('/profile')
                ->with('status', 'Please complete your applicant profile before applying.');
        }

        if (!Schema::hasTable('resumes')) {
            return back()->with('status', 'Resume module is not available yet.');
        }

        // Resume columns (support legacy names)
        $activeCol = Schema::hasColumn('resumes', 'is_active')
            ? 'is_active'
            : (Schema::hasColumn('resumes', 'active') ? 'active' : null);

        $aiParsedCol = Schema::hasColumn('resumes', 'ai_parsed')
            ? 'ai_parsed'
            : null;

        // Active resume
        $resumeQ = DB::table('resumes')->where('applicant_id', $applicantId)->orderByDesc('id');
        if ($activeCol) $resumeQ->where($activeCol, true);
        $resume = $resumeQ->first();

        if (!$resume) {
            return redirect('/app/resume')
                ->with('status', 'Please upload and activate a résumé first, then apply.');
        }

        if (!$aiParsedCol || empty($resume->{$aiParsedCol})) {
            return redirect('/app/resume')
                ->with('status', 'Your résumé is not parsed yet. Please parse/activate it, then try again.');
        }

        $vacancy = DB::table('vacancies')->where('id', $vacancyId)->first();
        abort_if(!$vacancy, 404);

        if (($vacancy->status ?? null) !== 'Open') {
            return back()->with('status', 'This vacancy is not open for applications.');
        }

        // Prevent duplicate active apps
        $existsActive = DB::table('applications')
            ->where('vacancy_id', $vacancyId)
            ->where('applicant_id', $applicantId)
            ->whereIn('status', ['submitted', 'in_review', 'shortlisted'])
            ->exists();

        if ($existsActive) {
            return back()->with('status', 'You already applied to this job.');
        }

        // Re-activate last withdrawn/rejected else create new
        $rehydrate = DB::table('applications')
            ->where('vacancy_id', $vacancyId)
            ->where('applicant_id', $applicantId)
            ->whereIn('status', ['withdrawn', 'rejected'])
            ->orderByDesc('id')
            ->first();

        $appId = null;

        DB::beginTransaction();

        try {
            if ($rehydrate?->id) {
                DB::table('applications')->where('id', $rehydrate->id)->update([
                    'status'          => 'submitted',
                    'resume_id'       => $resume->id,
                    'match_score'     => null,
                    'match_breakdown' => null,
                    'updated_at'      => now(),
                ]);
                $appId = (int) $rehydrate->id;
            } else {
                $appId = DB::table('applications')->insertGetId([
                    'vacancy_id'   => $vacancyId,
                    'applicant_id' => $applicantId,
                    'resume_id'    => $resume->id,
                    'status'       => 'submitted',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }

            // Normalize parsed resume JSON
            $norm = $this->readParsedResume($resume, $aiParsedCol);

            // Decode skills_required for scorer if needed
            $skillsRequired = $vacancy->skills_required ?? null;
            if (is_string($skillsRequired)) {
                $decoded = json_decode($skillsRequired, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $skillsRequired = $decoded;
                }
            }

            $vacArr = [
                'title'        => $vacancy->title ?? '',
                'description'  => $vacancy->description ?? '',
                'requirements' => $vacancy->requirements ?? ($vacancy->description ?? ''),

                'skills_required'           => $skillsRequired,
                'experience_min_years'      => $vacancy->experience_min_years ?? null,
                'experience_max_years'      => $vacancy->experience_max_years ?? null,
                'experience_years_required' => $vacancy->experience_years_required ?? null,
                'education_required'        => $vacancy->education_required ?? null,
            ];

            $payload = array_merge($norm['raw'], [
                'skills'           => $norm['skills'],
                'experience_years' => $norm['experience_years'],
                'education'        => $norm['education'],
                'certifications'   => $norm['certifications'],
                'keywords'         => $norm['keywords'],
            ]);

            $S = MatchScorer::score($payload, $vacArr);

            DB::table('applications')->where('id', $appId)->update([
                'match_score'     => (int) ($S['score'] ?? 0),
                'match_breakdown' => json_encode($S['breakdown'] ?? []),
                'updated_at'      => now(),
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('status', 'Failed to submit application. Please try again.');
        }

        // ✅ Notify HR AFTER commit
        try {
            $this->notifyHrApplicationSubmitted(
                applicationId: (int) $appId,
                vacancyId: $vacancyId,
                vacancyTitle: (string) ($vacancy->title ?? 'Vacancy'),
                applicantId: (int) $applicantId
            );
        } catch (\Throwable $e) {
            // swallow
        }

        return back()->with('status', 'Application submitted successfully.');
    }

    /**
     * ✅ Notify all HR users that a new application was submitted (#3)
     * - No Spatie roles dependency
     * - Optionally includes vacancy owner
     */
    private function notifyHrApplicationSubmitted(int $applicationId, int $vacancyId, string $vacancyTitle, int $applicantId): void
    {
        if (!Schema::hasTable('notifications')) return;
        if (!Schema::hasTable('users')) return;

        $applicantName = null;
        if (Schema::hasTable('applicants') && Schema::hasColumn('applicants', 'name')) {
            $nm = DB::table('applicants')->where('id', $applicantId)->value('name');
            $applicantName = $nm ? (string) $nm : null;
        }

        $ids = $this->hrRecipientIds($vacancyId);
        if (!$ids) return;

        $users = User::whereIn('id', $ids)->get();
        foreach ($users as $hr) {
            $hr->notify(new ApplicationSubmitted(
                applicationId: $applicationId,
                vacancyId: $vacancyId,
                vacancyTitle: $vacancyTitle,
                applicantId: $applicantId,
                applicantName: $applicantName
            ));
        }
    }

    /**
     * ✅ Find HR users safely across different schemas.
     * Supports:
     * - users.is_hr (boolean)
     * - users.role (string)
     * - users.user_type (string)
     * Also adds vacancy owner/creator if vacancies has a matching column.
     */
    private function hrRecipientIds(?int $vacancyId = null): array
    {
        $ids = [];

        // 1) is_hr boolean
        if (Schema::hasColumn('users', 'is_hr')) {
            $ids = array_merge($ids, DB::table('users')->where('is_hr', true)->pluck('id')->all());
        }

        // 2) role string
        if (Schema::hasColumn('users', 'role')) {
            $ids = array_merge($ids, DB::table('users')
                ->whereIn(DB::raw('lower(role)'), ['hr', 'hr_staff', 'hr-admin', 'admin', 'superadmin'])
                ->pluck('id')->all());
        }

        // 3) user_type string
        if (Schema::hasColumn('users', 'user_type')) {
            $ids = array_merge($ids, DB::table('users')
                ->whereIn(DB::raw('lower(user_type)'), ['hr', 'staff', 'admin'])
                ->pluck('id')->all());
        }

        // 4) vacancy owner (optional)
        if ($vacancyId && Schema::hasTable('vacancies')) {
            foreach (['created_by', 'user_id', 'hr_user_id', 'owner_user_id'] as $col) {
                if (Schema::hasColumn('vacancies', $col)) {
                    $ownerId = DB::table('vacancies')->where('id', $vacancyId)->value($col);
                    if ($ownerId) $ids[] = (int) $ownerId;
                    break;
                }
            }
        }

        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    /**
     * ✅ Resolve applicant id and AUTO-LINK email matched row to user_id.
     */
    protected function resolveApplicantId(bool $createIfMissing = false): ?int
    {
        $user = Auth::user();
        if (!$user) return null;

        if (!Schema::hasTable('applicants')) return null;

        $hasUserIdCol  = Schema::hasColumn('applicants', 'user_id');
        $hasEmailCol   = Schema::hasColumn('applicants', 'email');
        $hasNameCol    = Schema::hasColumn('applicants', 'name');
        $hasCreatedAt  = Schema::hasColumn('applicants', 'created_at');
        $hasUpdatedAt  = Schema::hasColumn('applicants', 'updated_at');

        if ($hasUserIdCol) {
            $row = DB::table('applicants')->where('user_id', $user->id)->first(['id', 'user_id', 'email']);
            if ($row?->id) return (int) $row->id;
        }

        if ($hasEmailCol && $user->email) {
            $row = DB::table('applicants')->where('email', $user->email)->first(['id', 'user_id', 'email']);
            if ($row?->id) {
                if ($hasUserIdCol && empty($row->user_id)) {
                    $update = ['user_id' => $user->id];
                    if ($hasUpdatedAt) $update['updated_at'] = now();
                    DB::table('applicants')->where('id', $row->id)->update($update);
                }

                if (Schema::hasColumn('users', 'applicant_id')) {
                    DB::table('users')->where('id', $user->id)->update([
                        'applicant_id' => $row->id,
                        'updated_at'   => now(),
                    ]);
                }

                return (int) $row->id;
            }
        }

        if (!$createIfMissing) return null;

        $payload = [];
        if ($hasUserIdCol) $payload['user_id'] = $user->id;
        if ($hasEmailCol)  $payload['email']   = $user->email;
        if ($hasNameCol)   $payload['name']    = $user->name ?? $user->email;
        if ($hasCreatedAt) $payload['created_at'] = now();
        if ($hasUpdatedAt) $payload['updated_at'] = now();

        if (empty($payload)) return null;

        try {
            $id = DB::table('applicants')->insertGetId($payload);

            if (Schema::hasColumn('users', 'applicant_id')) {
                DB::table('users')->where('id', $user->id)->update([
                    'applicant_id' => $id,
                    'updated_at'   => now(),
                ]);
            }

            return (int) $id;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Normalize AI-parsed résumé JSON to stable keys for scoring.
     */
    private function readParsedResume(object $resume, ?string $colName): array
    {
        if (!$resume || !$colName || !isset($resume->{$colName})) {
            return [
                'raw' => [], 'skills' => [], 'experience_years' => 0, 'education' => null,
                'certifications' => [], 'keywords' => [], 'experiences' => [], 'projects' => [],
            ];
        }

        $raw    = $resume->{$colName};
        $json   = is_array($raw) ? $raw : (json_decode($raw, true) ?: []);
        $aiText = property_exists($resume, 'ai_text') ? (string) $resume->ai_text : null;

        if (class_exists(ResumeHeuristicsParser::class)) {
            try {
                $json = ResumeHeuristicsParser::enrich($json, $aiText);
            } catch (\Throwable $e) {}
        }

        $skills = $json['skills'] ?? $json['skills_list'] ?? [];
        if (is_string($skills)) $skills = preg_split('/[,;|]+/', $skills) ?: [];
        $skills = array_values(array_unique(array_filter(array_map(
            fn ($s) => strtolower(trim((string) $s)),
            (array) $skills
        ))));

        $expValue = null;
        foreach ([
            'experience_years', 'years_experience', 'total_experience_years',
            'years_of_experience', 'exp_years', 'experience'
        ] as $k) {
            if (array_key_exists($k, $json)) { $expValue = $json[$k]; break; }
        }
        if (is_array($expValue)) $expValue = $expValue['years'] ?? $expValue['total'] ?? null;

        $expYears = (float) ($expValue ?? 0);

        if ($expYears <= 0 && !empty($json['work']) && is_array($json['work'])) {
            $expYears = (float) $this->deriveYearsFromWork($json['work']);
        }

        if ($expYears <= 0 && !empty($json['projects'])) {
            foreach ((array) $json['projects'] as $p) {
                $name = strtolower(trim((string) ($p['name'] ?? $p['title'] ?? '')));
                if (str_contains($name, 'final year project') || str_contains($name, 'capstone')) {
                    $expYears = max($expYears, 1.0);
                    break;
                }
            }
        }

        $education = $json['education'] ?? ($json['education_level'] ?? null);

        $certs = $json['certifications'] ?? [];
        if (is_string($certs)) $certs = preg_split('/[,;|]+/', $certs) ?: [];
        $certs = array_values(array_filter(array_map('trim', (array) $certs)));

        $keywords = $json['keywords'] ?? [];
        if (is_string($keywords)) $keywords = preg_split('/[,;|]+/', $keywords) ?: [];
        $keywords = array_values(array_filter(array_map('trim', (array) $keywords)));

        return [
            'raw'               => $json,
            'skills'            => $skills,
            'experience_years'  => max(0, (int) round($expYears)),
            'education'         => $education,
            'certifications'    => $certs,
            'keywords'          => $keywords,
            'experiences'       => $json['work'] ?? $json['experiences'] ?? [],
            'projects'          => $json['projects'] ?? [],
        ];
    }

    private function deriveYearsFromWork(array $work): int
    {
        $totalMonths = 0;

        foreach ($work as $w) {
            $start = $w['start'] ?? $w['from'] ?? $w['start_date'] ?? null;
            $end   = $w['end']   ?? $w['to']   ?? $w['end_date']   ?? null;
            if (!$start) continue;

            $s = $this->parseAnyDate($start);
            $e = $end ? $this->parseAnyDate($end) : Carbon::now();
            if (!$s || !$e) continue;

            $months = max(0, ($e->year - $s->year) * 12 + ($e->month - $s->month));

            if ($months === 0 && !empty($w['duration'])) {
                if (preg_match('/(\d+(?:\.\d+)?)\s*years?/i', $w['duration'], $m)) {
                    $months = (int) round((float) $m[1] * 12);
                } elseif (preg_match('/(\d+)\s*months?/i', $w['duration'], $m)) {
                    $months = (int) $m[1];
                }
            }

            $totalMonths += $months;
        }

        return (int) floor($totalMonths / 12);
    }

    private function parseAnyDate($value): ?Carbon
    {
        $txt = trim((string) $value);
        if ($txt === '') return null;

        $lower = strtolower($txt);
        if (in_array($lower, ['present', 'current', 'now'], true)) {
            return Carbon::now()->startOfMonth();
        }

        try {
            $txt = preg_replace('/[–—]/', '-', $txt);
            if (str_contains($txt, ' - ')) $txt = trim(explode(' - ', $txt)[0]);
            $c = Carbon::parse($txt);
            return $c ? $c->startOfMonth() : null;
        } catch (\Throwable $e) {}

        foreach (['Y-m-d', 'Y-m', 'Y/m/d', 'Y/m', 'M Y', 'F Y', 'Y'] as $fmt) {
            try {
                $c = Carbon::createFromFormat($fmt, (string) $value);
                if ($c) return $c->startOfMonth();
            } catch (\Throwable $e) {}
        }
        return null;
    }
}

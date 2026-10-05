<?php

namespace App\Http\Controllers\Applicant;

use App\Support\DbSchema;
use App\Http\Controllers\Concerns\ResolvesApplicant;
use App\Http\Controllers\Controller;
use App\Services\ApplicationScorer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

// ✅ Notifications
use App\Models\User;
use App\Notifications\ApplicationSubmitted;

class JobBrowserController extends Controller
{
    use ResolvesApplicant;

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

        if ($applicantId && DbSchema::hasTable('applications')) {
            $myApp = DB::table('applications')
                ->where('applicant_id', $applicantId)
                ->where('vacancy_id', $id)
                ->orderByDesc('id')
                ->first(['id', 'status']);

            $already = (bool) $myApp && $myApp->status !== 'withdrawn';
        }

        if ($applicantId && DbSchema::hasTable('resumes')) {
            $activeCol = DbSchema::hasColumn('resumes', 'is_active')
                ? 'is_active'
                : (DbSchema::hasColumn('resumes', 'active') ? 'active' : null);

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

        if (!DbSchema::hasTable('resumes')) {
            return back()->with('status', 'Resume module is not available yet.');
        }

        // Resume columns (support legacy names)
        $activeCol = DbSchema::hasColumn('resumes', 'is_active')
            ? 'is_active'
            : (DbSchema::hasColumn('resumes', 'active') ? 'active' : null);

        $aiParsedCol = DbSchema::hasColumn('resumes', 'ai_parsed')
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

            $S = ApplicationScorer::score($resume, $vacancy);

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
        if (!DbSchema::hasTable('notifications')) return;
        if (!DbSchema::hasTable('users')) return;

        $applicantName = null;
        if (DbSchema::hasTable('applicants') && DbSchema::hasColumn('applicants', 'name')) {
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
        if (DbSchema::hasColumn('users', 'is_hr')) {
            $ids = array_merge($ids, DB::table('users')->where('is_hr', true)->pluck('id')->all());
        }

        // 2) role string
        if (DbSchema::hasColumn('users', 'role')) {
            $ids = array_merge($ids, DB::table('users')
                ->whereIn(DB::raw('lower(role)'), ['hr', 'hr_staff', 'hr-admin', 'admin', 'superadmin'])
                ->pluck('id')->all());
        }

        // 3) user_type string
        if (DbSchema::hasColumn('users', 'user_type')) {
            $ids = array_merge($ids, DB::table('users')
                ->whereIn(DB::raw('lower(user_type)'), ['hr', 'staff', 'admin'])
                ->pluck('id')->all());
        }

        // 4) vacancy owner (optional)
        if ($vacancyId && DbSchema::hasTable('vacancies')) {
            foreach (['created_by', 'user_id', 'hr_user_id', 'owner_user_id'] as $col) {
                if (DbSchema::hasColumn('vacancies', $col)) {
                    $ownerId = DB::table('vacancies')->where('id', $vacancyId)->value($col);
                    if ($ownerId) $ids[] = (int) $ownerId;
                    break;
                }
            }
        }

        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

}

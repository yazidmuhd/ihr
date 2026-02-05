<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    /** first matching column helper */
    protected function col(string $t, string ...$names): ?string
    {
        foreach ($names as $n) {
            if (Schema::hasTable($t) && Schema::hasColumn($t, $n)) return $n;
        }
        return null;
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

    /**
     * Employees list
     * ✅ FIX: do NOT select e.hr_user_id unless it exists (avoid SQLSTATE[42703])
     * ✅ FIX: only join vacancies if applications join exists (avoid ap alias issues)
     */
    public function index(): Response
    {
        abort_unless(Schema::hasTable('employees'), 500, 'employees table not found.');

        // Employees columns (support variants)
        $eName   = $this->col('employees', 'name', 'full_name');
        $ePos    = $this->col('employees', 'position', 'job_title', 'title', 'role');
        $eHired  = $this->col('employees', 'hired_at', 'start_date', 'joined_at');
        $eHrCol  = $this->col('employees', 'hr_user_id', 'hr_staff_id', 'created_by', 'updated_by', 'user_id');
        $eApplId = $this->col('employees', 'applicant_id', 'candidate_id');
        $eAppId  = $this->col('employees', 'application_id');

        $q = DB::table('employees as e')->orderByDesc('e.id');

        // Track which joins we actually applied
        $joinedApplicants  = false;
        $joinedApplications = false;
        $joinedVacancies   = false;

        if (Schema::hasTable('applicants') && $eApplId) {
            $q->leftJoin('applicants as a', 'a.id', '=', "e.$eApplId");
            $joinedApplicants = true;
        }

        if (Schema::hasTable('applications') && $eAppId) {
            $q->leftJoin('applications as ap', 'ap.id', '=', "e.$eAppId");
            $joinedApplications = true;
        }

        // Join vacancies ONLY if we joined applications (ap alias must exist)
        $apVacancyId = $this->col('applications', 'vacancy_id', 'job_id');
        if ($joinedApplications && Schema::hasTable('vacancies') && $apVacancyId) {
            $q->leftJoin('vacancies as v', 'v.id', '=', "ap.$apVacancyId");
            $joinedVacancies = true;
        }

        // Build SELECT safely
        $select = [
            'e.id',

            // Normalize base fields into consistent keys
            $eName  ? DB::raw("e.\"$eName\" as \"name\"") : DB::raw("NULL as \"name\""),
            $ePos   ? DB::raw("e.\"$ePos\" as \"position\"") : DB::raw("NULL as \"position\""),
            $eHired ? DB::raw("e.\"$eHired\" as \"hired_at\"") : DB::raw("NULL as \"hired_at\""),

            // ✅ FIX: if missing, return NULL as hr_user_id
            $eHrCol ? DB::raw("e.\"$eHrCol\" as \"hr_user_id\"") : DB::raw("NULL as \"hr_user_id\""),

            // Keep IDs (also normalized)
            $eApplId ? DB::raw("e.\"$eApplId\" as \"applicant_id\"") : DB::raw("NULL as \"applicant_id\""),
            $eAppId  ? DB::raw("e.\"$eAppId\" as \"application_id\"") : DB::raw("NULL as \"application_id\""),

            // Timestamps (if present)
            Schema::hasColumn('employees', 'created_at') ? 'e.created_at' : DB::raw("NULL as created_at"),
            Schema::hasColumn('employees', 'updated_at') ? 'e.updated_at' : DB::raw("NULL as updated_at"),
        ];

        if ($joinedApplicants && Schema::hasColumn('applicants', 'email')) {
            $select[] = DB::raw('a.email as applicant_email');
        } else {
            $select[] = DB::raw('NULL as applicant_email');
        }

        if ($joinedVacancies && Schema::hasColumn('vacancies', 'title')) {
            $select[] = DB::raw('v.title as vacancy_title');
        } elseif ($joinedVacancies && Schema::hasColumn('vacancies', 'name')) {
            $select[] = DB::raw('v.name as vacancy_title');
        } else {
            $select[] = DB::raw('NULL as vacancy_title');
        }

        $employees = $q->select($select)->get();

        return Inertia::render('HR/Employees/Index', [
            'employees' => $employees,
        ]);
    }

    /** ✅ Employee detail + candidate history (also schema-safe for applicant_id/application_id/hr_user_id) */
    public function show(int $id): Response
    {
        abort_unless(Schema::hasTable('employees'), 500, 'employees table not found.');

        $raw = DB::table('employees')->where('id', $id)->first();
        abort_unless($raw, 404, 'Employee not found.');

        // Map employee fields safely
        $eName   = $this->col('employees', 'name', 'full_name');
        $ePos    = $this->col('employees', 'position', 'job_title', 'title', 'role');
        $eHired  = $this->col('employees', 'hired_at', 'start_date', 'joined_at');
        $eHrCol  = $this->col('employees', 'hr_user_id', 'hr_staff_id', 'created_by', 'updated_by', 'user_id');
        $eApplId = $this->col('employees', 'applicant_id', 'candidate_id');
        $eAppId  = $this->col('employees', 'application_id');

        $employee = (object) [
            'id'             => $raw->id,
            'name'           => $eName ? ($raw->{$eName} ?? null) : null,
            'position'       => $ePos ? ($raw->{$ePos} ?? null) : null,
            'hired_at'       => $eHired ? ($raw->{$eHired} ?? null) : null,
            'hr_user_id'     => $eHrCol ? ($raw->{$eHrCol} ?? null) : null,
            'applicant_id'   => $eApplId ? ($raw->{$eApplId} ?? null) : null,
            'application_id' => $eAppId ? ($raw->{$eAppId} ?? null) : null,
            'created_at'     => property_exists($raw, 'created_at') ? ($raw->created_at ?? null) : null,
            'updated_at'     => property_exists($raw, 'updated_at') ? ($raw->updated_at ?? null) : null,
        ];

        // Candidate (applicant)
        $candidate = null;
        if (Schema::hasTable('applicants') && !empty($employee->applicant_id)) {
            $candidate = DB::table('applicants')->where('id', $employee->applicant_id)->first();
        }

        // Application + Vacancy + Resume
        $application = null;
        $vacancy = null;
        $resume = null;

        if (Schema::hasTable('applications') && !empty($employee->application_id)) {
            $application = DB::table('applications')->where('id', $employee->application_id)->first();

            if ($application && Schema::hasTable('vacancies')) {
                $vacIdCol = $this->col('applications', 'vacancy_id', 'job_id');
                if ($vacIdCol && !empty($application->{$vacIdCol})) {
                    $vacancy = DB::table('vacancies')->where('id', $application->{$vacIdCol})->first();
                }
            }

            // resume (if applications has resume_id)
            if ($application && Schema::hasTable('resumes') && Schema::hasColumn('applications', 'resume_id')) {
                $resumeId = $application->resume_id ?? null;
                if ($resumeId) {
                    $rPath = $this->col('resumes', 'file_path', 'path', 'stored_path', 'resume_path', 'url');
                    $rName = $this->col('resumes', 'original_name', 'filename', 'name', 'file_name');
                    $r = DB::table('resumes')->where('id', $resumeId)->first();

                    if ($r && $rPath) {
                        $resume = (object)[
                            'id'   => $r->id,
                            'name' => $rName ? ($r->{$rName} ?? 'Resume') : 'Resume',
                            'url'  => $this->buildPublicUrl($r->{$rPath} ?? null),
                        ];
                    }
                }
            }
        }

        // Interview + Panels
        $interview = null;
        $panels = [];

        if (Schema::hasTable('interviews') && !empty($employee->application_id)) {
            $interview = DB::table('interviews')
                ->where('application_id', $employee->application_id)
                ->orderByDesc('scheduled_at')
                ->orderByDesc('id')
                ->first();

            if ($interview && Schema::hasTable('interview_panels')) {
                $panelNoCol = Schema::hasColumn('interview_panels', 'panel_no')
                    ? 'panel_no'
                    : (Schema::hasColumn('interview_panels', 'panel') ? 'panel' : null);

                if ($panelNoCol) {
                    $panels = DB::table('interview_panels')
                        ->where('interview_id', $interview->id)
                        ->orderBy($panelNoCol, 'asc')
                        ->get()
                        ->map(function ($p) use ($panelNoCol) {
                            $p->panel_no = $p->{$panelNoCol};
                            return $p;
                        })
                        ->all();
                }
            }
        }

        return Inertia::render('HR/Employees/Show', [
            'employee'     => $employee,
            'candidate'    => $candidate,
            'application'  => $application,
            'vacancy'      => $vacancy,
            'resume'       => $resume,
            'interview'    => $interview,
            'panels'       => $panels,
        ]);
    }
}

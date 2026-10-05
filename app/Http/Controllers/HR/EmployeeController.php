<?php

namespace App\Http\Controllers\HR;

use App\Services\ActivityFeed;
use App\Services\Uploads;
use App\Support\DbSchema;
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
            if (DbSchema::hasTable($t) && DbSchema::hasColumn($t, $n)) return $n;
        }
        return null;
    }

    /** Résumé link for HR (a short-lived signed URL when uploads are on a private disk) */
    protected function buildPublicUrl(?string $pathOrUrl): ?string
    {
        return Uploads::url($pathOrUrl);
    }

    /**
     * Employees list
     * ✅ FIX: do NOT select e.hr_user_id unless it exists (avoid SQLSTATE[42703])
     * ✅ FIX: only join vacancies if applications join exists (avoid ap alias issues)
     */
    public function index(): Response
    {
        abort_unless(DbSchema::hasTable('employees'), 500, 'employees table not found.');

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

        if (DbSchema::hasTable('applicants') && $eApplId) {
            $q->leftJoin('applicants as a', 'a.id', '=', "e.$eApplId");
            $joinedApplicants = true;
        }

        if (DbSchema::hasTable('applications') && $eAppId) {
            $q->leftJoin('applications as ap', 'ap.id', '=', "e.$eAppId");
            $joinedApplications = true;
        }

        // Join vacancies ONLY if we joined applications (ap alias must exist)
        $apVacancyId = $this->col('applications', 'vacancy_id', 'job_id');
        if ($joinedApplications && DbSchema::hasTable('vacancies') && $apVacancyId) {
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
            DbSchema::hasColumn('employees', 'created_at') ? 'e.created_at' : DB::raw("NULL as created_at"),
            DbSchema::hasColumn('employees', 'updated_at') ? 'e.updated_at' : DB::raw("NULL as updated_at"),
        ];

        if ($joinedApplicants && DbSchema::hasColumn('applicants', 'email')) {
            $select[] = DB::raw('a.email as applicant_email');
        } else {
            $select[] = DB::raw('NULL as applicant_email');
        }

        if ($joinedVacancies && DbSchema::hasColumn('vacancies', 'title')) {
            $select[] = DB::raw('v.title as vacancy_title');
        } elseif ($joinedVacancies && DbSchema::hasColumn('vacancies', 'name')) {
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
        abort_unless(DbSchema::hasTable('employees'), 500, 'employees table not found.');

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
        if (DbSchema::hasTable('applicants') && !empty($employee->applicant_id)) {
            $candidate = DB::table('applicants')->where('id', $employee->applicant_id)->first();
        }

        // Application + Vacancy + Resume
        $application = null;
        $vacancy = null;
        $resume = null;

        if (DbSchema::hasTable('applications') && !empty($employee->application_id)) {
            $application = DB::table('applications')->where('id', $employee->application_id)->first();

            if ($application && DbSchema::hasTable('vacancies')) {
                $vacIdCol = $this->col('applications', 'vacancy_id', 'job_id');
                if ($vacIdCol && !empty($application->{$vacIdCol})) {
                    $vacancy = DB::table('vacancies')->where('id', $application->{$vacIdCol})->first();
                }
            }

            // resume (if applications has resume_id)
            if ($application && DbSchema::hasTable('resumes') && DbSchema::hasColumn('applications', 'resume_id')) {
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

        if (DbSchema::hasTable('interviews') && !empty($employee->application_id)) {
            $interview = DB::table('interviews')
                ->where('application_id', $employee->application_id)
                ->orderByDesc('scheduled_at')
                ->orderByDesc('id')
                ->first();

            if ($interview && DbSchema::hasTable('interview_panels')) {
                $panelNoCol = DbSchema::hasColumn('interview_panels', 'panel_no')
                    ? 'panel_no'
                    : (DbSchema::hasColumn('interview_panels', 'panel') ? 'panel' : null);

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
            'timeline'     => $application ? ActivityFeed::statusTimeline((int) $application->id) : [],
        ]);
    }
}

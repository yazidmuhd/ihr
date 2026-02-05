<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        // ✅ Safe counts (won't crash if table doesn't exist yet)
        $stats = [
            'vacancies'    => Schema::hasTable('vacancies')    ? (int) DB::table('vacancies')->count()    : 0,
            'applications' => Schema::hasTable('applications') ? (int) DB::table('applications')->count() : 0,
            'interviews'   => Schema::hasTable('interviews')   ? (int) DB::table('interviews')->count()   : 0,
            'employees'    => Schema::hasTable('employees')    ? (int) DB::table('employees')->count()    : 0,
        ];

        // Vacancies by status (bar chart)
        $byStatus = ['labels' => [], 'values' => []];
        if (Schema::hasTable('vacancies')) {
            $rowsStatus = DB::table('vacancies')
                ->select('status', DB::raw('COUNT(*)::int as count'))
                ->groupBy('status')
                ->orderBy('status')
                ->get();

            $byStatus = [
                'labels' => $rowsStatus->pluck('status')->map(fn ($v) => $v ?: '—')->values(),
                'values' => $rowsStatus->pluck('count')->values(),
            ];
        }

        // Open vacancies by department (pie chart)
        $byDept = ['labels' => [], 'values' => []];
        if (Schema::hasTable('vacancies') && Schema::hasColumn('vacancies', 'department')) {
            $rowsDept = DB::table('vacancies')
                ->select('department', DB::raw('COUNT(*)::int as count'))
                ->where('status', 'Open')
                ->groupBy('department')
                ->orderBy('department')
                ->get();

            $byDept = [
                'labels' => $rowsDept->pluck('department')->map(fn ($v) => $v ?: '—')->values(),
                'values' => $rowsDept->pluck('count')->values(),
            ];
        }

        /**
         * Recent applications (latest 8)
         * Tolerant to applicants schema differences.
         */
        $recentApps = collect();
        if (Schema::hasTable('applications') && Schema::hasTable('vacancies')) {
            $hasApplicants = Schema::hasTable('applicants');

            $labelExpr = "'Applicant #' || a.applicant_id"; // default if no applicants table
            if ($hasApplicants) {
                $nameExpr = null;
                if (Schema::hasColumn('applicants', 'name')) $nameExpr = 'ap.name';
                elseif (Schema::hasColumn('applicants', 'full_name')) $nameExpr = 'ap.full_name';

                $hasEmail = Schema::hasColumn('applicants', 'email');

                if ($nameExpr && $hasEmail) {
                    $labelExpr = "COALESCE(NULLIF(TRIM($nameExpr), ''), NULLIF(TRIM(ap.email), ''), 'Applicant #' || a.applicant_id)";
                } elseif ($nameExpr) {
                    $labelExpr = "COALESCE(NULLIF(TRIM($nameExpr), ''), 'Applicant #' || a.applicant_id)";
                } elseif ($hasEmail) {
                    $labelExpr = "COALESCE(NULLIF(TRIM(ap.email), ''), 'Applicant #' || a.applicant_id)";
                }
            }

            $q = DB::table('applications as a')
                ->join('vacancies as v', 'v.id', '=', 'a.vacancy_id')
                ->orderByDesc('a.created_at')
                ->limit(8);

            if ($hasApplicants) {
                $q->leftJoin('applicants as ap', 'ap.id', '=', 'a.applicant_id');
            }

            $recentApps = $q->get([
                'a.id',
                DB::raw("$labelExpr as applicant"),
                'v.title as vacancy',
                'a.status',
                DB::raw("to_char(a.created_at, 'YYYY-MM-DD HH24:MI') as created_at"),
            ]);
        }

        // Latest 6 vacancies
        $latestVacancies = collect();
        if (Schema::hasTable('vacancies')) {
            $latestVacancies = DB::table('vacancies')
                ->orderByDesc('created_at')
                ->limit(6)
                ->get([
                    'id', 'title', 'department', 'location', 'status',
                    DB::raw("to_char(created_at, 'YYYY-MM-DD') as created_at"),
                ]);
        }

        return Inertia::render('HR/Dashboard', [
            'user'            => fn () => Auth::user()?->only('id', 'name', 'username', 'email', 'is_hr'),
            'stats'           => $stats,
            'byStatus'        => $byStatus,
            'byDept'          => $byDept,
            'recentApps'      => $recentApps,
            'latestVacancies' => $latestVacancies,
        ]);
    }
}

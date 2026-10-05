<?php

namespace App\Http\Controllers\Applicant;

use App\Support\DbSchema;
use App\Http\Controllers\Concerns\ResolvesApplicant;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use ResolvesApplicant;

    public function index(Request $request): Response
    {
        $user = Auth::user();
        abort_unless($user, 403);

        // ✅ Most important: resolve applicant_id in a way that matches existing records
        $applicantId = $this->resolveApplicantId(true);

        $stats = [
            'applications' => 0,
            'interviews'   => 0,
            'hasResume'    => false,
        ];

        $byStatus = ['labels' => [], 'values' => []];

        if ($applicantId) {
            // Applications count (match Applications page = count ALL statuses)
            $stats['applications'] = (int) DB::table('applications')
                ->where('applicant_id', $applicantId)
                ->count();

            // Interviews count (supports either interviews table OR fallback to statuses)
            $stats['interviews'] = $this->countInterviewsForApplicant($applicantId);

            // Active resume
            $stats['hasResume'] = $this->hasActiveResume($applicantId);

            // Status chart data
            $groups = DB::table('applications')
                ->select('status', DB::raw('count(*) as c'))
                ->where('applicant_id', $applicantId)
                ->groupBy('status')
                ->orderByRaw("
                    CASE
                        WHEN status = 'submitted' THEN 1
                        WHEN status = 'in_review' THEN 2
                        WHEN status = 'shortlisted' THEN 3
                        WHEN status LIKE 'interview%' THEN 4
                        WHEN status = 'hired' THEN 5
                        WHEN status = 'rejected' THEN 6
                        WHEN status = 'withdrawn' THEN 7
                        ELSE 99
                    END
                ")
                ->get();

            foreach ($groups as $g) {
                $byStatus['labels'][] = $g->status ?? 'unknown';
                $byStatus['values'][] = (int) ($g->c ?? 0);
            }
        }

        return Inertia::render('Applicant/Dashboard', [
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'welcome' => 'Welcome to your i-HR dashboard!',
            'stats'   => $stats,
            'byStatus'=> $byStatus,
        ]);
    }

    private function hasActiveResume(int $applicantId): bool
    {
        if (!DbSchema::hasTable('resumes')) return false;

        $activeCol = DbSchema::hasColumn('resumes', 'is_active')
            ? 'is_active'
            : (DbSchema::hasColumn('resumes', 'active') ? 'active' : null);

        $q = DB::table('resumes')->where('applicant_id', $applicantId);

        if ($activeCol) $q->where($activeCol, true);

        return $q->exists();
    }

    private function countInterviewsForApplicant(int $applicantId): int
    {
        // ✅ If you have interviews table, count real interview rows
        if (DbSchema::hasTable('interviews')) {
            // Case A: interviews.application_id exists
            if (DbSchema::hasColumn('interviews', 'application_id')) {
                return (int) DB::table('interviews as i')
                    ->join('applications as a', 'a.id', '=', 'i.application_id')
                    ->where('a.applicant_id', $applicantId)
                    ->count('i.id');
            }

            // Case B: interviews.applicant_id exists
            if (DbSchema::hasColumn('interviews', 'applicant_id')) {
                return (int) DB::table('interviews')
                    ->where('applicant_id', $applicantId)
                    ->count('id');
            }
        }

        // ✅ Fallback: if you track interviews only via application status
        return (int) DB::table('applications')
            ->where('applicant_id', $applicantId)
            ->where(function ($q) {
                $q->where('status', 'ilike', 'interview%')
                  ->orWhereIn('status', ['shortlisted']);
            })
            ->count();
    }

}

<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
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
        if (!Schema::hasTable('resumes')) return false;

        $activeCol = Schema::hasColumn('resumes', 'is_active')
            ? 'is_active'
            : (Schema::hasColumn('resumes', 'active') ? 'active' : null);

        $q = DB::table('resumes')->where('applicant_id', $applicantId);

        if ($activeCol) $q->where($activeCol, true);

        return $q->exists();
    }

    private function countInterviewsForApplicant(int $applicantId): int
    {
        // ✅ If you have interviews table, count real interview rows
        if (Schema::hasTable('interviews')) {
            // Case A: interviews.application_id exists
            if (Schema::hasColumn('interviews', 'application_id')) {
                return (int) DB::table('interviews as i')
                    ->join('applications as a', 'a.id', '=', 'i.application_id')
                    ->where('a.applicant_id', $applicantId)
                    ->count('i.id');
            }

            // Case B: interviews.applicant_id exists
            if (Schema::hasColumn('interviews', 'applicant_id')) {
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

    /**
     * ✅ Robust resolver:
     * 1) users.applicant_id (MOST IMPORTANT for your case)
     * 2) applicants.user_id
     * 3) applicants.email (if column exists)
     * 4) create minimal applicants row (optional)
     */
    protected function resolveApplicantId(bool $createIfMissing = false): ?int
    {
        $user = Auth::user();
        if (!$user) return null;

        if (!Schema::hasTable('applicants')) return null;

        // 1) Use users.applicant_id if it exists (this fixes your mismatch case)
        if (Schema::hasColumn('users', 'applicant_id')) {
            $ua = (int) ($user->applicant_id ?? 0);
            if ($ua > 0) {
                $exists = DB::table('applicants')->where('id', $ua)->exists();
                if ($exists) {
                    // If applicants.user_id exists, ensure it's linked
                    if (Schema::hasColumn('applicants', 'user_id')) {
                        DB::table('applicants')->where('id', $ua)->update([
                            'user_id'    => $user->id,
                            'updated_at' => now(),
                        ]);
                    }
                    return $ua;
                }
            }
        }

        // 2) Try by applicants.user_id
        if (Schema::hasColumn('applicants', 'user_id')) {
            $row = DB::table('applicants')->where('user_id', $user->id)->first();
            if ($row?->id) return (int) $row->id;
        }

        // 3) Try by email if applicants.email exists
        if (Schema::hasColumn('applicants', 'email')) {
            $row = DB::table('applicants')->where('email', $user->email)->first();
            if ($row?->id) {
                // Link it to this user to prevent duplicates
                if (Schema::hasColumn('applicants', 'user_id')) {
                    DB::table('applicants')->where('id', $row->id)->update([
                        'user_id'    => $user->id,
                        'updated_at' => now(),
                    ]);
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

        // 4) Create minimal applicant record
        $payload = [];
        if (Schema::hasColumn('applicants', 'user_id')) $payload['user_id'] = $user->id;
        if (Schema::hasColumn('applicants', 'email'))   $payload['email']   = $user->email;
        if (Schema::hasColumn('applicants', 'name'))    $payload['name']    = $user->name ?? $user->email;

        if (Schema::hasColumn('applicants', 'created_at')) $payload['created_at'] = now();
        if (Schema::hasColumn('applicants', 'updated_at')) $payload['updated_at'] = now();

        if (empty($payload)) return null;

        $id = DB::table('applicants')->insertGetId($payload);

        if (Schema::hasColumn('users', 'applicant_id')) {
            DB::table('users')->where('id', $user->id)->update([
                'applicant_id' => $id,
                'updated_at'   => now(),
            ]);
        }

        return (int) $id;
    }
}

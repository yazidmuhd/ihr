<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Services\ActivityFeed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    /** GET /hr/activity  — audit trail with filters; ?application=ID shows one candidate's full history */
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'area'        => ['nullable', 'string', 'max:40'],
            'user'        => ['nullable', 'string', 'max:20'],
            'from'        => ['nullable', 'date_format:Y-m-d'],
            'to'          => ['nullable', 'date_format:Y-m-d'],
            'application' => ['nullable', 'integer', 'min:1'],
        ]);

        $applicationId = (int) ($filters['application'] ?? 0);
        $application = null;

        if ($applicationId) {
            $application = DB::table('applications as a')
                ->join('vacancies as v', 'v.id', '=', 'a.vacancy_id')
                ->join('applicants as p', 'p.id', '=', 'a.applicant_id')
                ->where('a.id', $applicationId)
                ->first(['a.id', 'a.status', 'v.title as vacancy', 'p.name as applicant']);
        }

        return Inertia::render('HR/Activity/Index', [
            'entries'     => ActivityFeed::auditPage($filters),
            'filters'     => $filters,
            'areas'       => ActivityFeed::AREAS,
            'users'       => DB::table('users')->where('is_hr', true)->orderBy('name')->get(['id', 'name']),
            'application' => $application,
            'timeline'    => $application ? ActivityFeed::statusTimeline($applicationId) : [],
        ]);
    }
}

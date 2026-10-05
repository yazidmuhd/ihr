<?php

namespace App\Http\Controllers\Applicant;

use App\Support\DbSchema;
use App\Http\Controllers\Concerns\ResolvesApplicant;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\ApplicationWithdrawn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationsController extends Controller
{
    use ResolvesApplicant;

    /** List my applications */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $applicantId = $this->resolveApplicantId(true);

        if (!$applicantId) {
            return Inertia::render('Applicant/Applications/Index', [
                'rows' => [
                    'data' => [],
                    'links' => [],
                ],
                'note' => 'Please complete your applicant profile before viewing applications.',
            ]);
        }

        // Build query
        $q = DB::table('applications as a')
            ->join('vacancies as v', 'v.id', '=', 'a.vacancy_id')
            ->where('a.applicant_id', $applicantId)
            ->orderByDesc('a.id');

        // Only join resumes if table exists AND applications has resume_id
        $hasResumes = DbSchema::hasTable('resumes') && DbSchema::hasColumn('applications', 'resume_id');
        if ($hasResumes) {
            $q->leftJoin('resumes as r', 'r.id', '=', 'a.resume_id');
        }

        // Deadline field selection (in case your schema differs)
        $deadlineSelect = DbSchema::hasColumn('vacancies', 'closing_date')
            ? DB::raw("to_char(v.closing_date, 'YYYY-MM-DD') as deadline")
            : DB::raw("null as deadline");

        $rows = $q->select(array_filter([
                'a.id',
                'a.status',
                'a.match_score',
                'a.match_breakdown',
                DB::raw("to_char(a.created_at, 'YYYY-MM-DD') as applied_at"),

                'v.id as vacancy_id',
                'v.title',
                'v.department',
                'v.location',
                'v.employment_type',
                $deadlineSelect,

                // Optional resume fields (only if joined)
                $hasResumes ? 'a.resume_id' : null,
            ]))
            ->paginate(10)
            ->withQueryString();

        // Decode breakdown JSON for frontend
        $rows->getCollection()->transform(function ($row) {
            if (isset($row->match_breakdown) && is_string($row->match_breakdown)) {
                $decoded = json_decode($row->match_breakdown, true);
                $row->match_breakdown = is_array($decoded) ? $decoded : [];
            } else {
                $row->match_breakdown = [];
            }
            return $row;
        });

        return Inertia::render('Applicant/Applications/Index', [
            'rows' => $rows,
        ]);
    }

    /** Withdraw an application */
    public function withdraw(Request $request, int $id)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $applicantId = $this->resolveApplicantId(false);
        if (!$applicantId) {
            return back()->with('status', 'Please complete your applicant profile first.');
        }

        // Grab application + vacancy id so HR can navigate back to vacancy board
        $app = DB::table('applications')
            ->where('id', $id)
            ->where('applicant_id', $applicantId)
            ->first(['id', 'status', 'vacancy_id']);

        abort_if(!$app, 404);

        if (in_array((string)$app->status, ['withdrawn', 'rejected'], true)) {
            return back()->with('status', 'This application cannot be withdrawn.');
        }

        // vacancy title for notification message
        $vacancyTitle = 'Vacancy';
        if (!empty($app->vacancy_id) && DbSchema::hasTable('vacancies')) {
            $t = DB::table('vacancies')->where('id', $app->vacancy_id)->value('title');
            if ($t) $vacancyTitle = (string) $t;
        }

        DB::table('applications')->where('id', $id)->update([
            'status'     => 'withdrawn',
            'updated_at' => now(),
        ]);

        // ✅ Notify HR after withdraw (#4)
        try {
            $this->notifyHrApplicationWithdrawn(
                applicationId: (int) $app->id,
                vacancyId: (int) ($app->vacancy_id ?? 0),
                vacancyTitle: $vacancyTitle,
                applicantId: (int) $applicantId
            );
        } catch (\Throwable $e) {
            // swallow - never break user flow
        }

        return back()->with('status', 'Application withdrawn.');
    }

    /**
     * ✅ Notify HR users that an application was withdrawn (#4)
     * - Works even if you DON'T use Spatie Roles
     * - Also optionally includes vacancy owner (created_by/user_id/hr_user_id/etc) if those columns exist
     */
    private function notifyHrApplicationWithdrawn(int $applicationId, int $vacancyId, string $vacancyTitle, int $applicantId): void
    {
        if (!DbSchema::hasTable('users')) return;

        // Applicant name (optional)
        $applicantName = null;
        if (DbSchema::hasTable('applicants') && DbSchema::hasColumn('applicants', 'name')) {
            $nm = DB::table('applicants')->where('id', $applicantId)->value('name');
            $applicantName = $nm ? (string) $nm : null;
        }

        // HR recipients (schema-safe)
        $ids = $this->hrRecipientIds($vacancyId);
        if (!$ids) return;

        // If notifications table doesn't exist, don't throw
        if (!DbSchema::hasTable('notifications')) return;

        $users = User::whereIn('id', $ids)->get();
        foreach ($users as $hr) {
            $hr->notify(new ApplicationWithdrawn(
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

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        return $ids;
    }

}

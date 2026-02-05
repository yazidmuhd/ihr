<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

use App\Models\User;
use App\Notifications\JobVacancyPosted;

class VacancyController extends Controller
{
    public function index(Request $request): Response
    {
        $q      = trim((string) $request->get('q', ''));
        $dept   = (string) $request->get('dept', 'All');
        $status = (string) $request->get('status', 'All');
        $type   = (string) $request->get('type', 'All');

        $query = DB::table('vacancies')
            ->select([
                'id',
                'title',
                'department',
                'location',
                'employment_type as type',
                'status',
                DB::raw("to_char(created_at, 'YYYY-MM-DD') as created_at"),
            ])
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('title', 'ilike', "%$q%")
                        ->orWhere('department', 'ilike', "%$q%")
                        ->orWhere('location', 'ilike', "%$q%");
                });
            })
            ->when($dept !== 'All', function ($qq) use ($dept) {
                $qq->where('department', $dept);
            })
            ->when($status !== 'All', function ($qq) use ($status) {
                $qq->where('status', $status);
            })
            ->when($type !== 'All', function ($qq) use ($type) {
                $qq->where('employment_type', $type);
            })
            ->orderByDesc('id');

        $rows = $query->paginate(12)->withQueryString();

        $departments = $this->departmentOptions();

        $typesFromDb = DB::table('vacancies')
            ->select('employment_type')
            ->distinct()
            ->orderBy('employment_type')
            ->pluck('employment_type')
            ->filter()
            ->values()
            ->all();

        $types = collect(['permanent', 'contract', 'intern'])
            ->merge($typesFromDb)
            ->unique()
            ->values()
            ->all();

        $statuses = ['Open', 'Closed', 'Archived'];

        return Inertia::render('HR/Vacancies/Index', [
            'filters' => [
                'q'      => $q,
                'dept'   => $dept,
                'type'   => $type,
                'status' => $status,
            ],
            'departments' => $departments,
            'types'       => $types,
            'statuses'    => $statuses,
            'rows'        => $rows,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HR/Vacancies/Create', [
            'departments' => $this->departmentOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'           => ['required', 'string', 'max:200'],
            'department'      => ['nullable', 'string', 'max:120'],
            'location'        => ['nullable', 'string', 'max:120'],
            'employment_type' => ['required', 'string', 'in:permanent,contract,intern'],
            'description'     => ['nullable', 'string'],
            'deadline'        => ['nullable', 'date'],
            'status'          => ['nullable', 'in:Open,Closed,Archived'],

            'experience_min_years' => ['nullable', 'integer', 'min:0', 'max:50'],
            'experience_max_years' => ['nullable', 'integer', 'min:0', 'max:50'],

            'education_required' => ['nullable', 'string', 'max:200'],

            'skills_required'   => ['nullable', 'array'],
            'skills_required.*' => ['string', 'max:255'],
        ]);

        $actorId = $request->user()?->id;

        $min = $data['experience_min_years'] ?? null;
        $max = $data['experience_max_years'] ?? null;

        if ($min !== null && $max !== null && $max < $min) {
            [$min, $max] = [$max, $min];
        }

        $skills = $data['skills_required'] ?? null;
        if (is_array($skills)) {
            $skills = array_values(
                array_filter(
                    array_map('trim', $skills),
                    fn ($s) => $s !== ''
                )
            );
        }

        $status = $data['status'] ?? 'Open';

        $vacancyId = DB::table('vacancies')->insertGetId([
            'title'           => $data['title'],
            'department'      => $data['department'] ?? null,
            'location'        => $data['location'] ?? null,
            'employment_type' => $data['employment_type'],
            'description'     => $data['description'] ?? null,
            'closing_date'    => $data['deadline'] ?? null,
            'status'          => $status,

            'created_by' => $actorId,

            'experience_min_years'      => $min,
            'experience_max_years'      => $max,
            'experience_years_required' => $min,

            'education_required' => $data['education_required'] ?? null,
            'skills_required'    => $skills ? json_encode($skills) : null,

            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($status === 'Open') {
            $closingDate = $data['deadline'] ?? null;
            $this->notifyApplicantsVacancyOpen($vacancyId, $data['title'], $closingDate);
        }

        return redirect()
            ->route('hr.vacancies.index')
            ->with('status', 'Vacancy created.');
    }

    public function setStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => ['required', 'in:Open,Closed,Archived'],
        ]);

        $newStatus = (string) $request->input('status');

        $vacancy = DB::table('vacancies')
            ->select('id', 'title', 'status', 'closing_date')
            ->where('id', $id)
            ->first();

        abort_if(!$vacancy, 404);

        $oldStatus = (string) ($vacancy->status ?? '');

        DB::table('vacancies')->where('id', $id)->update([
            'status'     => $newStatus,
            'updated_at' => now(),
        ]);

        if ($oldStatus !== 'Open' && $newStatus === 'Open') {
            $closingDate = $vacancy->closing_date ? (string) $vacancy->closing_date : null;
            $this->notifyApplicantsVacancyOpen((int) $vacancy->id, (string) $vacancy->title, $closingDate);
        }

        return back()->with('status', 'Status updated.');
    }

    public function destroy(int $id)
    {
        DB::table('vacancies')->where('id', $id)->delete();
        return back()->with('status', 'Vacancy deleted.');
    }

    public function edit(int $id): Response
    {
        $vacancy = DB::table('vacancies')->where('id', $id)->first();
        abort_if(!$vacancy, 404);

        if (isset($vacancy->skills_required) && is_string($vacancy->skills_required)) {
            $decoded = json_decode($vacancy->skills_required, true);
            $vacancy->skills_required = is_array($decoded) ? $decoded : [];
        }

        $departments = $this->departmentOptions();
        $statuses    = ['Open', 'Closed', 'Archived'];
        $types       = ['permanent', 'contract', 'intern'];

        return Inertia::render('HR/Vacancies/Edit', [
            'vacancy'     => $vacancy,
            'departments' => $departments,
            'statuses'    => $statuses,
            'types'       => $types,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'title'           => ['required', 'string', 'max:200'],
            'department'      => ['nullable', 'string', 'max:120'],
            'location'        => ['nullable', 'string', 'max:120'],
            'employment_type' => ['required', 'string', 'in:permanent,contract,intern'],
            'description'     => ['nullable', 'string'],
            'deadline'        => ['nullable', 'date'],
            'status'          => ['required', 'in:Open,Closed,Archived'],

            'experience_min_years' => ['nullable', 'integer', 'min:0', 'max:50'],
            'experience_max_years' => ['nullable', 'integer', 'min:0', 'max:50'],
            'education_required'   => ['nullable', 'string', 'max:200'],

            'skills_required'   => ['nullable', 'array'],
            'skills_required.*' => ['string', 'max:255'],
        ]);

        $before = DB::table('vacancies')
            ->select('id', 'title', 'status', 'closing_date')
            ->where('id', $id)
            ->first();

        abort_if(!$before, 404);

        $oldStatus = (string) ($before->status ?? '');

        $min = $data['experience_min_years'] ?? null;
        $max = $data['experience_max_years'] ?? null;

        if ($min !== null && $max !== null && $max < $min) {
            [$min, $max] = [$max, $min];
        }

        $skills = $data['skills_required'] ?? null;
        if (is_array($skills)) {
            $skills = array_values(
                array_filter(
                    array_map('trim', $skills),
                    fn ($s) => $s !== ''
                )
            );
        }

        $newStatus = $data['status'];

        DB::table('vacancies')->where('id', $id)->update([
            'title'           => $data['title'],
            'department'      => $data['department'] ?? null,
            'location'        => $data['location'] ?? null,
            'employment_type' => $data['employment_type'],
            'description'     => $data['description'] ?? null,
            'closing_date'    => $data['deadline'] ?? null,
            'status'          => $newStatus,

            'experience_min_years'      => $min,
            'experience_max_years'      => $max,
            'experience_years_required' => $min,

            'education_required' => $data['education_required'] ?? null,
            'skills_required'    => $skills ? json_encode($skills) : null,

            'updated_at' => now(),
        ]);

        if ($oldStatus !== 'Open' && $newStatus === 'Open') {
            $closingDate = $data['deadline'] ?? null;
            $this->notifyApplicantsVacancyOpen((int) $before->id, $data['title'], $closingDate);
        }

        return redirect()
            ->route('hr.vacancies.index')
            ->with('status', 'Vacancy updated.');
    }

    /**
     * Notify all applicant users when a vacancy is OPEN
     */
    private function notifyApplicantsVacancyOpen(int $vacancyId, string $vacancyTitle, ?string $closingDate = null): void
    {
        User::role('applicant')
            ->orderBy('id')
            ->chunk(500, function ($users) use ($vacancyId, $vacancyTitle, $closingDate) {
                foreach ($users as $u) {
                    $u->notify(new JobVacancyPosted($vacancyId, $vacancyTitle, $closingDate));
                }
            });
    }

    private function departmentOptions(): array
    {
        $defaults = [
            'Engineering',
            'IT',
            'HR',
            'Finance',
            'Operations',
            'Marketing',
            'Sales',
            'Customer Support',
            'Product',
            'Design',
            'Legal',
        ];

        $fromDb = DB::table('vacancies')
            ->whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department')
            ->all();

        return collect($defaults)
            ->merge($fromDb)
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}

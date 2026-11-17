<?php

namespace Database\Seeders;

use App\Models\User;
use App\Notifications\InterviewInvited;
use App\Services\ApplicationScorer;
use App\Services\Uploads;
use Illuminate\Database\Seeder;
use Illuminate\Http\File;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Fictional data for the public demo (DEMO_MODE=true): the two demo accounts, six vacancies
 * and about twenty applicants at every stage of hiring. All names, e-mails and résumés are
 * invented; the résumé PDFs in database/demo/resumes say so on the first line.
 *
 * Each step is recorded as the person who would do it (set_config('app.user_id')), so the
 * audit log and hiring timelines read like real use.
 */
class DemoSeeder extends Seeder
{
    private int $hrId;

    private const VACANCIES = [
        'software' => ['title' => 'Software Engineer (Laravel)', 'department' => 'IT', 'location' => 'Hybrid (Gebeng + Remote)', 'employment_type' => 'permanent', 'status' => 'Open',
            'skills' => ['Laravel', 'PHP', 'PostgreSQL', 'Vue', 'REST API', 'Git'], 'min' => 2, 'max' => 5, 'education' => "Bachelor's Degree in Computer Science",
            'description' => 'Build and maintain internal web systems for HR and plant operations using Laravel, Vue and PostgreSQL.'],
        'process' => ['title' => 'Process Engineer', 'department' => 'Engineering', 'location' => 'Gebeng, Kuantan (On-site)', 'employment_type' => 'permanent', 'status' => 'Open',
            'skills' => ['Process Safety', 'HAZOP', 'Root Cause Analysis', 'P&ID', 'SAP PM'], 'min' => 3, 'max' => 8, 'education' => "Bachelor's Degree in Chemical Engineering",
            'description' => 'Support daily production, lead process safety studies and drive reliability improvements on site.'],
        'hr' => ['title' => 'HR Executive (Talent Acquisition)', 'department' => 'HR', 'location' => 'Kuala Lumpur', 'employment_type' => 'permanent', 'status' => 'Open',
            'skills' => ['Recruitment', 'Onboarding', 'Employee Relations', 'HRIS', 'Microsoft Excel'], 'min' => 2, 'max' => 5, 'education' => "Bachelor's Degree in Human Resource Management",
            'description' => 'Run end-to-end hiring for operations and engineering roles, from job posting to onboarding.'],
        'maintenance' => ['title' => 'Maintenance Technician (Rotating Equipment)', 'department' => 'Operations', 'location' => 'Gebeng, Kuantan (On-site)', 'employment_type' => 'contract', 'status' => 'Open',
            'skills' => ['Preventive Maintenance', 'Rotating Equipment', 'LOTO', 'Troubleshooting'], 'min' => 1, 'max' => 4, 'education' => 'Diploma',
            'description' => 'Keep pumps, motors and gearboxes running safely through planned and corrective maintenance.'],
        'analyst' => ['title' => 'Data Analyst Intern', 'department' => 'IT', 'location' => 'Kuala Lumpur', 'employment_type' => 'intern', 'status' => 'Open',
            'skills' => ['SQL', 'Python', 'Power BI', 'Microsoft Excel'], 'min' => 0, 'max' => 1, 'education' => 'Diploma',
            'description' => 'Six-month internship building dashboards and reports for the operations team.'],
        'finance' => ['title' => 'Finance Executive', 'department' => 'Finance', 'location' => 'Kuala Lumpur', 'employment_type' => 'permanent', 'status' => 'Closed',
            'skills' => ['Financial Reporting', 'SAP', 'Microsoft Excel'], 'min' => 2, 'max' => 4, 'education' => "Bachelor's Degree in Accounting",
            'description' => 'Month-end closing and management reporting. Applications for this role have closed.'],
    ];

    /**
     * Fictional applicants: résumé template, what the résumé reader found, and where each
     * application stands. Stages: submitted, in_review, rejected, withdrawn, invited,
     * confirmed, done, scored, hired.
     */
    private const PEOPLE = [
        ['Hakim Zainal', 'software', ['Laravel', 'PHP', 'PostgreSQL', 'Vue', 'REST API', 'Git', 'Docker'], 5, "Bachelor's Degree in Computer Science", ['software' => 'hired']],
        ['Tan Mei Ling', 'software', ['Laravel', 'PHP', 'MySQL', 'Vue', 'Git'], 3, "Bachelor's Degree in Software Engineering", ['software' => 'confirmed']],
        ['Arjun Nair', 'software', ['PHP', 'PostgreSQL', 'REST API', 'Docker'], 2, "Bachelor's Degree in Computer Science", ['software' => 'scored', 'analyst' => 'submitted']],
        ['Danial Rosli', 'software', ['JavaScript', 'Vue', 'Git'], 1, 'Diploma in Computer Science', ['software' => 'in_review']],
        ['Siti Nurhaliza Omar', 'software', ['Python', 'SQL'], 1, 'Diploma in Information Technology', ['software' => 'rejected', 'analyst' => 'in_review']],
        ['Kevin Lau', 'software', ['Laravel', 'PHP', 'Git'], 2, "Bachelor's Degree in Information Systems", ['software' => 'submitted']],
        ['Farid Hassan', 'process', ['Process Safety', 'HAZOP', 'Root Cause Analysis', 'P&ID', 'SAP PM'], 7, "Bachelor's Degree in Chemical Engineering", ['process' => 'hired']],
        ['Amirul Hakimi', 'process', ['Process Safety', 'HAZOP', 'P&ID'], 4, "Bachelor's Degree in Chemical Engineering", ['process' => 'done']],
        ['Priya Ramasamy', 'process', ['Root Cause Analysis', 'SAP PM'], 3, "Bachelor's Degree in Mechanical Engineering", ['process' => 'in_review']],
        ['Zulkifli Ahmad', 'process', ['Preventive Maintenance', 'Root Cause Analysis'], 2, 'Diploma in Chemical Engineering', ['process' => 'submitted', 'maintenance' => 'invited']],
        ['Wong Hui Min', 'process', ['Process Safety', 'P&ID'], 5, "Master's Degree in Process Engineering", ['process' => 'withdrawn']],
        ['Nadia Iskandar', 'hr', ['Recruitment', 'Onboarding', 'Employee Relations', 'HRIS', 'Microsoft Excel'], 4, "Bachelor's Degree in Human Resource Management", ['hr' => 'scored']],
        ['Lim Jia Hui', 'hr', ['Recruitment', 'Microsoft Excel'], 2, "Bachelor's Degree in Business Administration", ['hr' => 'submitted']],
        ['Rajesh Kumar', 'hr', ['Payroll', 'Microsoft Excel'], 1, 'Diploma in Business Studies', ['hr' => 'rejected', 'finance' => 'rejected']],
        ['Sofea Aziz', 'hr', ['Recruitment', 'Onboarding', 'HRIS'], 3, "Bachelor's Degree in Human Resource Management", ['hr' => 'confirmed']],
        ['Ikhwan Musa', 'process', ['Preventive Maintenance', 'Rotating Equipment', 'LOTO', 'Troubleshooting'], 3, 'Diploma in Mechanical Engineering', ['maintenance' => 'in_review']],
        ['Chong Wei Jie', 'process', ['Rotating Equipment', 'Troubleshooting'], 1, 'Diploma in Electrical Engineering', ['maintenance' => 'submitted']],
        ['Aleesya Hamdan', 'software', ['SQL', 'Python', 'Power BI', 'Microsoft Excel'], 0, 'Diploma in Statistics', ['analyst' => 'confirmed']],
        ['Haziq Firdaus', 'hr', ['Financial Reporting', 'SAP', 'Microsoft Excel'], 3, "Bachelor's Degree in Accounting", ['finance' => 'in_review']],
    ];

    private const TEMPLATES = ['software' => 'software-engineer.pdf', 'process' => 'process-engineer.pdf', 'hr' => 'hr-executive.pdf'];

    public function run(): void
    {
        $this->call(DepartmentSeeder::class);

        $accounts = config('demo.accounts');
        $this->hrId = $this->user($accounts['hr'], 'hr')->id;

        $vacancyIds = [];
        foreach (self::VACANCIES as $key => $v) {
            $vacancyIds[$key] = $this->vacancy($v);
        }

        foreach (self::PEOPLE as $i => [$name, $template, $skills, $years, $education, $applications]) {
            $email = Str::slug($name, '.') . '@example.com';
            $user = $this->user(['name' => $name, 'email' => $email, 'username' => Str::slug($name, '.')], 'applicant');
            $this->applyAll($user, $template, $skills, $years, $education, $applications, $vacancyIds, daysAgo: 20 - $i);
        }

        // The demo applicant: an interview invitation to answer, plus an application in review
        $demo = $this->user($accounts['applicant'], 'applicant');
        $this->applyAll($demo, 'software', ['Laravel', 'PHP', 'PostgreSQL', 'Vue', 'Git'], 3,
            "Bachelor's Degree in Information Systems", ['software' => 'invited', 'hr' => 'in_review'], $vacancyIds, daysAgo: 2);

        $this->actingAs(null);
    }

    private function user(array $account, string $role): User
    {
        $user = User::firstOrNew(['email' => $account['email']]);
        $user->forceFill([
            'name'              => $account['name'],
            'username'          => $account['username'],
            'role'              => $role,
            'is_hr'             => $role === 'hr',
            'password'          => config('demo.password'),
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }

    private function vacancy(array $v): int
    {
        return DB::table('vacancies')->insertGetId([
            'title'                => $v['title'],
            'department'           => $v['department'],
            'location'             => $v['location'],
            'employment_type'      => $v['employment_type'],
            'description'          => $v['description'],
            'closing_date'         => $v['status'] === 'Open' ? now()->addDays(30)->toDateString() : now()->subDays(3)->toDateString(),
            'status'               => $v['status'],
            'skills_required'      => json_encode($v['skills']),
            'experience_min_years' => $v['min'],
            'experience_max_years' => $v['max'],
            'education_required'   => $v['education'],
            'created_by'           => $this->hrId,
            'created_at'           => now()->subDays(25),
            'updated_at'           => now()->subDays(25),
        ]);
    }

    private function applyAll(User $user, string $template, array $skills, int $years, string $education,
                              array $applications, array $vacancyIds, int $daysAgo): void
    {
        $this->actingAs($user->id);
        $applicantId = DB::table('applicants')->insertGetId([
            'user_id' => $user->id, 'name' => $user->name, 'email' => $user->email,
            'created_at' => now()->subDays($daysAgo + 1), 'updated_at' => now()->subDays($daysAgo + 1),
        ]);

        $resumeId = $this->resume($applicantId, $template, $skills, $years, $education, $daysAgo + 1);
        $resume = DB::table('resumes')->find($resumeId);

        foreach ($applications as $vacancyKey => $stage) {
            $this->application($user, $applicantId, $resume, $vacancyIds[$vacancyKey], $stage, now()->subDays($daysAgo));
        }
    }

    private function resume(int $applicantId, string $template, array $skills, int $years, string $education, int $daysAgo): int
    {
        $source = database_path('demo/resumes/' . self::TEMPLATES[$template]);
        $path = Uploads::disk()->putFileAs('resumes', new File($source), "demo-{$applicantId}-" . self::TEMPLATES[$template]);

        return DB::table('resumes')->insertGetId([
            'applicant_id' => $applicantId,
            'filename'     => self::TEMPLATES[$template],
            'path'         => $path,
            'size'         => filesize($source),
            'mime'         => 'application/pdf',
            'is_active'    => true,
            'ai_status'    => 'done',
            'ai_parsed'    => json_encode(['skills' => $skills, 'years_experience' => $years, 'education' => $education, 'demo' => true]),
            'created_at'   => now()->subDays($daysAgo),
            'updated_at'   => now()->subDays($daysAgo),
        ]);
    }

    private function application(User $user, int $applicantId, object $resume, int $vacancyId, string $stage, Carbon $at): void
    {
        $vacancy = DB::table('vacancies')->find($vacancyId);
        $score = ApplicationScorer::score($resume, $vacancy);

        $this->actingAs($user->id);
        $appId = DB::table('applications')->insertGetId([
            'applicant_id'    => $applicantId,
            'vacancy_id'      => $vacancyId,
            'resume_id'       => $resume->id,
            'status'          => 'submitted',
            'match_score'     => (int) ($score['score'] ?? 0),
            'match_breakdown' => json_encode($score['breakdown'] ?? []),
            'created_at'      => $at,
            'updated_at'      => $at,
        ]);

        $interviewStages = ['invited', 'confirmed', 'done', 'scored', 'hired'];
        $path = match (true) {
            $stage === 'withdrawn'                 => [['withdrawn', $user->id]],
            $stage === 'in_review'                 => [['in_review', $this->hrId]],
            $stage === 'rejected'                  => [['in_review', $this->hrId], ['rejected', $this->hrId]],
            in_array($stage, $interviewStages, true) => [['shortlisted', $this->hrId]],
            default                                => [],
        };
        foreach ($path as $n => [$status, $by]) {
            $this->actingAs($by);
            DB::table('applications')->where('id', $appId)->update(['status' => $status, 'updated_at' => $at->copy()->addDays($n + 1)]);
        }

        if (in_array($stage, $interviewStages, true)) {
            $this->interview($user, $applicantId, $appId, $vacancy, $stage, (int) ($score['score'] ?? 0), $at);
        }

        // Spread the status history over the days after applying, as it would happen
        $history = DB::table('application_status_histories')->where('application_id', $appId)->orderBy('id')->pluck('id');
        foreach ($history as $n => $id) {
            DB::table('application_status_histories')->where('id', $id)->update(['created_at' => $at->copy()->addDays($n)->addHours(2)]);
        }
    }

    private function interview(User $user, int $applicantId, int $appId, object $vacancy, string $stage, int $resumeScore, Carbon $at): void
    {
        $this->actingAs($this->hrId);
        $past = in_array($stage, ['done', 'scored', 'hired'], true);
        $when = $past ? $at->copy()->addDays(5)->setTime(10, 0) : now()->addDays(4)->setTime(10, 0);
        $online = $vacancy->department === 'IT';

        $interviewId = DB::table('interviews')->insertGetId([
            'application_id'   => $appId,
            'applicant_id'     => $applicantId,
            'vacancy_id'       => $vacancy->id,
            'scheduled_at'     => $when,
            'mode'             => $online ? 'online' : 'onsite',
            'location'         => $online ? null : 'Demo Office, Gebeng, Kuantan',
            'meeting_link'     => $online ? 'https://meet.example.com/ihr-demo' : null,
            'extra_info'       => 'Fictional interview created for the i-HR demo.',
            'status'           => $past ? 'done' : 'scheduled',
            'candidate_status' => $stage === 'invited' ? 'pending' : 'confirmed',
            'created_by'       => $this->hrId,
            'created_at'       => $at->copy()->addDays(2),
            'updated_at'       => $at->copy()->addDays(2),
        ]);

        if ($stage === 'invited') {
            // In-app only: seeding never sends e-mail
            $user->notifyNow(new InterviewInvited($interviewId, $vacancy->id, $vacancy->title, $when->toIso8601String(),
                $online ? 'online' : 'onsite', $online ? null : 'Demo Office, Gebeng, Kuantan', $online ? 'https://meet.example.com/ihr-demo' : null), ['database']);
        }

        if (! in_array($stage, ['scored', 'hired'], true)) {
            return;
        }

        $ratings = $stage === 'hired' ? [5, 4] : [4, 3];
        foreach ($ratings as $n => $rating) {
            DB::table('interview_panels')->insert([
                'interview_id' => $interviewId, 'panel_no' => $n + 1, 'name' => 'Panel ' . ($n + 1),
                'rating' => $rating, 'created_at' => $when, 'updated_at' => $when,
            ]);
        }
        $interviewScore = (int) round(array_sum($ratings) / count($ratings) / 5 * 100);
        DB::table('interviews')->where('id', $interviewId)->update([
            'status' => 'scored', 'panel_count' => count($ratings),
            'interview_score' => $interviewScore,
            'final_score' => (int) round($resumeScore * 0.6 + $interviewScore * 0.4),
        ]);

        if ($stage === 'hired') {
            DB::table('applications')->where('id', $appId)->update(['status' => 'hired', 'updated_at' => $when->copy()->addDay()]);
            DB::table('interviews')->where('id', $interviewId)->update(['candidate_status' => 'hired']);
            DB::table('employees')->insert([
                'employee_no'    => 'EMP' . $when->format('Ymd') . '-' . strtoupper(Str::random(4)),
                'name'           => $user->name,
                'email'          => $user->email,
                'job_title'      => $vacancy->title,
                'department'     => $vacancy->department,
                'hired_at'       => $when->copy()->addDay(),
                'status'         => 'active',
                'applicant_id'   => $applicantId,
                'application_id' => $appId,
                'vacancy_id'     => $vacancy->id,
                'interview_id'   => $interviewId,
                'hr_user_id'     => $this->hrId,
                'created_at'     => $when->copy()->addDay(),
                'updated_at'     => $when->copy()->addDay(),
            ]);
        }
    }

    /** Who the database triggers record for the next changes (audit log, status history) */
    private function actingAs(?int $userId): void
    {
        DB::select("select set_config('app.user_id', ?, false)", [(string) ($userId ?? '')]);
    }
}

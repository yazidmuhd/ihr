<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();   // tests don't need compiled frontend assets
    }

    protected function hrUser(): User
    {
        return User::factory()->hr()->create();
    }

    /** An applicant user with an applicant profile; returns [user, applicantId] */
    protected function applicant(array $attributes = []): array
    {
        $user = User::factory()->create($attributes);

        $applicantId = DB::table('applicants')->insertGetId([
            'user_id'    => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user, $applicantId];
    }

    /** A résumé that has already been parsed by the AI (no API call needed) */
    protected function parsedResume(int $applicantId, array $parsed = []): int
    {
        return DB::table('resumes')->insertGetId([
            'applicant_id' => $applicantId,
            'filename'     => 'cv.pdf',
            'path'         => 'resumes/cv.pdf',
            'is_active'    => true,
            'ai_status'    => 'done',
            'ai_parsed'    => json_encode($parsed ?: [
                'skills'           => ['SQL', 'Python', 'Excel'],
                'experience_years' => 2,
                'education'        => 'Bachelor of Computer Science',
            ]),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    protected function vacancy(array $attributes = []): int
    {
        DB::table('departments')->insertOrIgnore(['name' => 'IT', 'created_at' => now(), 'updated_at' => now()]);

        return DB::table('vacancies')->insertGetId($attributes + [
            'title'                => 'Data Analyst',
            'department'           => 'IT',
            'employment_type'      => 'permanent',
            'description'          => 'SQL and Python analytics',
            'status'               => 'Open',
            'skills_required'      => json_encode(['SQL', 'Python', 'Excel']),
            'experience_min_years' => 0,
            'experience_max_years' => 3,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);
    }
}

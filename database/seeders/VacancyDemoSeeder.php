<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VacancyDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Demo data for local / staging only (skipped in production by DatabaseSeeder)
        DB::table('vacancies')->updateOrInsert(['title' => 'Backend Developer (Laravel)'], [
            'department' => 'IT',
            'location' => 'Kuala Lumpur',
            'employment_type' => 'permanent',
            'description' => 'Build APIs in Laravel; PostgreSQL experience preferred.',
            'closing_date' => now()->addDays(30)->toDateString(),
            'status' => 'Open',
            'skills_required' => json_encode(['Laravel', 'PHP', 'PostgreSQL', 'REST', 'Communication']),
            'education_required' => "Bachelor's Degree",
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

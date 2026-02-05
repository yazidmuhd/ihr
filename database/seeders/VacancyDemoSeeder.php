<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VacancyDemoSeeder extends Seeder
{
    public function run(): void
    {
        $vacancyId = DB::table('vacancies')->insertGetId([
            'title' => 'Backend Developer (Laravel)',
            'department' => 'IT',
            'location' => 'Kuala Lumpur',
            'employment_type' => 'permanent',
            'description' => 'Build APIs in Laravel; PostgreSQL experience preferred.',
            'closing_date' => now()->addDays(30)->toDateString(),
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rules = [
            ['rule_type'=>'skill','pattern'=>'laravel','weight'=>5],
            ['rule_type'=>'skill','pattern'=>'php','weight'=>4],
            ['rule_type'=>'skill','pattern'=>'postgresql','weight'=>3],
            ['rule_type'=>'skill','pattern'=>'rest','weight'=>2],
            ['rule_type'=>'experience','pattern'=>'years experience','weight'=>1],
            ['rule_type'=>'education','pattern'=>'bachelor','weight'=>2],
            ['rule_type'=>'softskill','pattern'=>'communication','weight'=>1],
        ];
        foreach ($rules as $r) {
            DB::table('vacancy_rules')->insert([
                'vacancy_id' => $vacancyId,
                'rule_type' => $r['rule_type'],
                'pattern' => $r['pattern'],
                'weight' => $r['weight'],
                'is_regex' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

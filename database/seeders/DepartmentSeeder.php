<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Customer Support', 'code' => 'CS'],
            ['name' => 'Design',           'code' => 'DSN'],
            ['name' => 'Engineering',      'code' => 'ENG'],
            ['name' => 'Finance',          'code' => 'FIN'],
            ['name' => 'HR',               'code' => 'HR'],
            ['name' => 'IT',               'code' => 'IT'],
            ['name' => 'Legal',            'code' => 'LGL'],
            ['name' => 'Marketing',        'code' => 'MKT'],
            ['name' => 'Operations',       'code' => 'OPS'],
            ['name' => 'Product',          'code' => 'PRD'],
            ['name' => 'Sales',            'code' => 'SLS'],
        ];

        foreach ($departments as $d) {
            DB::table('departments')->updateOrInsert(
                ['name' => $d['name']],
                $d + ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}

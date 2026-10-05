<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Safe to run on a live database: departments and the HR account are upserted,
     * and demo data is only added outside production.
     */
    public function run(): void
    {
        $this->call(DepartmentSeeder::class);
        $this->call(HrUserSeeder::class);

        if (! app()->environment('production')) {
            $this->call(VacancyDemoSeeder::class);
        }
    }
}

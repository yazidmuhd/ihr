<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\HrUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    /** Run a seeder directly (db:seed asks for confirmation in production) */
    private function runSeeder(string $class): void
    {
        $this->app->make($class)->setContainer($this->app)->__invoke();
    }

    public function test_seeding_creates_departments_and_the_hr_account(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(11, DB::table('departments')->count());
        $hr = User::where('username', 'humanres')->firstOrFail();
        $this->assertSame('hr', $hr->role);
        $this->assertTrue($hr->is_hr);
    }

    public function test_reseeding_never_resets_a_changed_password(): void
    {
        $this->seed(HrUserSeeder::class);
        User::where('username', 'humanres')->update(['password' => Hash::make('a-new-strong-password')]);

        $this->seed(HrUserSeeder::class);

        $this->assertTrue(Hash::check('a-new-strong-password', User::where('username', 'humanres')->value('password')));
    }

    public function test_production_refuses_to_seed_without_a_strong_admin_password(): void
    {
        $this->app['env'] = 'production';
        $this->setAdminPassword('short');

        try {
            $this->expectException(\RuntimeException::class);
            $this->runSeeder(HrUserSeeder::class);
        } finally {
            $this->setAdminPassword(null);
            $this->app['env'] = 'testing';
        }
    }

    public function test_staging_also_requires_a_strong_admin_password(): void
    {
        $this->app['env'] = 'staging';
        $this->setAdminPassword(null);

        try {
            $this->expectException(\RuntimeException::class);
            $this->runSeeder(HrUserSeeder::class);
        } finally {
            $this->app['env'] = 'testing';
        }
    }

    public function test_production_seeding_skips_demo_data(): void
    {
        $this->app['env'] = 'production';
        $this->setAdminPassword('a-very-strong-password-123');

        try {
            $this->runSeeder(DatabaseSeeder::class);
            $this->assertSame(0, DB::table('vacancies')->count());
        } finally {
            $this->setAdminPassword(null);
            $this->app['env'] = 'testing';
        }
    }

    /**
     * env() reads $_SERVER and $_ENV before getenv(), so a HR_ADMIN_PASSWORD line in .env
     * (CI copies .env.example) would hide a putenv() alone. Set or clear all three.
     */
    private function setAdminPassword(?string $value): void
    {
        if ($value === null) {
            putenv('HR_ADMIN_PASSWORD');
            unset($_ENV['HR_ADMIN_PASSWORD'], $_SERVER['HR_ADMIN_PASSWORD']);
            return;
        }
        putenv("HR_ADMIN_PASSWORD={$value}");
        $_ENV['HR_ADMIN_PASSWORD'] = $_SERVER['HR_ADMIN_PASSWORD'] = $value;
    }
}

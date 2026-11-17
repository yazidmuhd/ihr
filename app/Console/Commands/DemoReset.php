<?php

namespace App\Console\Commands;

use App\Services\Uploads;
use Illuminate\Console\Command;

/**
 * Wipes the public demo back to its starting state: empty uploads, fresh database,
 * demo accounts and fictional data (DemoSeeder). Runs on every deploy of the demo.
 */
class DemoReset extends Command
{
    protected $signature = 'demo:reset';

    protected $description = 'Reset the public demo to fresh fictional data (DEMO_MODE only)';

    public function handle(): int
    {
        if (! config('demo.enabled')) {
            $this->error('demo:reset only runs when DEMO_MODE=true. Nothing was changed.');

            return self::FAILURE;
        }

        foreach (['resumes', 'avatars'] as $dir) {
            Uploads::disk()->deleteDirectory($dir);
        }

        $this->call('migrate:fresh', ['--force' => true, '--seed' => true]);

        $this->info('Demo data restored.');

        return self::SUCCESS;
    }
}

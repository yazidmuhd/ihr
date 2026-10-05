<?php

namespace App\Console\Commands;

use App\Services\Uploads;
use Illuminate\Console\Command;

/**
 * Wipes the public test site back to its demo state: fresh database, seed data,
 * no uploaded files. Refuses to run in production.
 *
 *   php artisan ihr:reset-demo           (staging; schedule it, e.g. weekly)
 */
class ResetDemo extends Command
{
    protected $signature = 'ihr:reset-demo';

    protected $description = 'Reset the staging (public test) site to fresh demo data';

    public function handle(): int
    {
        if (!app()->environment('staging', 'local')) {
            $this->error('ihr:reset-demo only runs in staging or local (APP_ENV=' . app()->environment() . ').');
            return self::FAILURE;
        }

        if (app()->environment('local') && !$this->confirm('This deletes your local database data and uploads. Continue?')) {
            return self::FAILURE;
        }

        foreach (['resumes', 'avatars'] as $dir) {
            Uploads::disk()->deleteDirectory($dir);
        }
        $this->info('Uploaded résumés and avatars removed.');

        $this->call('migrate:fresh', ['--force' => true, '--seed' => true]);

        $this->info('Demo data restored.');
        return self::SUCCESS;
    }
}

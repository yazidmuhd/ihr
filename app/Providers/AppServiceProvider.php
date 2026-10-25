<?php

namespace App\Providers;

use App\Support\DbSchema;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Inertia::share('csrf_token', fn () => csrf_token());

        // The cached schema map must be rebuilt after any migration
        Event::listen(MigrationsEnded::class, fn () => DbSchema::flush());

        // One password policy for register, reset and change (docs/SECURITY.md).
        // Production also rejects passwords found in known breaches (Have I Been Pwned,
        // k-anonymity: only the first 5 characters of the SHA-1 hash leave the server).
        Password::defaults(function () {
            $rule = Password::min(12)->max(128)->mixedCase()->numbers();

            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });
    }
}

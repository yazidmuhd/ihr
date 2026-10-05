<?php

namespace App\Providers;

use App\Support\DbSchema;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
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
    }
}

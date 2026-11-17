<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureUserHasRole;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: base_path('routes/web.php'),
        commands: base_path('routes/console.php'),
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Real visitor IP from the hosting proxy (config/network.php), before anything reads $request->ip()
        $middleware->prepend(\App\Http\Middleware\UseClientIpHeader::class);

        // HTTPS ends at the hosting proxy, which forwards plain HTTP plus X-Forwarded-Proto.
        // Trust only that header (not X-Forwarded-For) so links and signed URLs use https://.
        $middleware->trustProxies(at: '*', headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO);
        $middleware->append(\App\Http\Middleware\NoIndexOutsideProduction::class);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        $middleware->web(append: [
            // Office / VPN IP allowlist; runs after the session starts so it can see HR accounts
            \App\Http\Middleware\RestrictToAllowedIps::class,
            // Disabled accounts and idle HR sessions are signed out (docs/SECURITY.md)
            \App\Http\Middleware\EnsureAccountIsActive::class,
            // Public demo only: blocks actions that would spoil it for the next visitor
            \App\Http\Middleware\ProtectDemo::class,
            \App\Http\Middleware\LogoutIdleHrUsers::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\SetAuditUser::class,
        ]);

        // Register our “role” alias so routes can use ->middleware('role:hr')
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);

        // Ensure auth middleware class is discoverable (Laravel provides it)
        // No alias needed; 'auth' is built-in.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

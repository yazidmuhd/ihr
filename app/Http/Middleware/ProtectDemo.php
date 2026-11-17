<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public demo only (DEMO_MODE=true): blocks the few actions that would spoil the demo
 * for the next visitor. Everything else is allowed, and `demo:reset` restores the data.
 */
class ProtectDemo
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('demo.enabled')) {
            return $next($request);
        }

        $route = $request->route()?->getName();

        if ($route === 'hr.vacancies.destroy') {
            // An error (not a status message), so the page reports it instead of "deleted".
            // 303: this runs before Inertia's middleware, which would otherwise turn the
            // redirect after a DELETE into a 303 itself (a 302 repeats the DELETE).
            return back(303)->withErrors(['demo' => 'Deleting vacancies is turned off in the demo. You can close or archive them instead.']);
        }

        // Demo accounts keep the password shown on the login page
        if (in_array($route, ['password.email', 'password.update'], true)
            && in_array(strtolower((string) $request->input('email')), $this->demoEmails(), true)) {
            return back()->with('status', 'Demo account passwords cannot be changed.');
        }

        return $next($request);
    }

    /** @return string[] */
    private function demoEmails(): array
    {
        return array_map('strtolower', array_column(config('demo.accounts'), 'email'));
    }
}

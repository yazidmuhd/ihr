<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Unauthenticated');
        }

        $role = strtolower(trim($role));

        // Your system: is_hr boolean
        if ($role === 'hr' && !$user->is_hr) {
            abort(403, 'HR access only');
        }

        if ($role === 'applicant' && $user->is_hr) {
            abort(403, 'Applicant access only');
        }

        return $next($request);
    }
}

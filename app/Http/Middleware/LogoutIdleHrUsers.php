<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * HR sessions end after HR_IDLE_MINUTES without a request (an unattended office
 * laptop), while applicants keep the normal SESSION_LIFETIME.
 */
class LogoutIdleHrUsers
{
    private const KEY = 'hr_last_seen';

    public function handle(Request $request, Closure $next): Response
    {
        $minutes = config('security.hr_idle_minutes');
        $user    = $request->user();

        if ($minutes > 0 && $user?->is_hr) {
            $last = $request->session()->get(self::KEY);

            if ($last && now()->timestamp - $last > $minutes * 60) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('status', "You were signed out after {$minutes} minutes of inactivity.");
            }

            $request->session()->put(self::KEY, now()->timestamp);
        }

        return $next($request);
    }
}

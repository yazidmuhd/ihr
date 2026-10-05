<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Internal-access control by network (office IPs and VPN egress ranges).
 * Configured in config/network.php; does nothing when IP_ALLOWLIST is empty.
 *
 * Scope "all": every page requires an allowed IP.
 * Scope "hr":  the HR area and anything an HR account does require an allowed IP;
 *              applicants can still register and apply from anywhere.
 */
class RestrictToAllowedIps
{
    public function handle(Request $request, Closure $next)
    {
        $allowlist = config('network.allowlist', []);

        if (!$allowlist || $request->is(...config('network.allowlist_exempt', []))) {
            return $next($request);
        }

        if ($this->needsAllowedIp($request) && !IpUtils::checkIp((string) $request->ip(), $allowlist)) {
            return response()->view('errors.ip-blocked', ['ip' => $request->ip()], 403);
        }

        return $next($request);
    }

    private function needsAllowedIp(Request $request): bool
    {
        if (config('network.allowlist_scope') !== 'hr') {
            return true;
        }

        return $request->is('hr', 'hr/*') || (bool) $request->user()?->is_hr;
    }
}

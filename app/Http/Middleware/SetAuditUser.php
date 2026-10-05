<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Tells PostgreSQL who is acting for this request, so the audit-log and
 * application-status-history triggers can record the user.
 * Always set (empty for guests) so a reused connection never keeps a stale user.
 */
class SetAuditUser
{
    public function handle(Request $request, Closure $next)
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::select("select set_config('app.user_id', ?, false)", [(string) ($request->user()?->id ?? '')]);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Behind a hosting proxy every request arrives from the proxy's address.
 * This replaces it with the visitor's IP from the proxy's header (CLIENT_IP_HEADER),
 * so $request->ip() is correct everywhere: the IP allowlist, login rate limiting
 * and the sessions table.
 *
 * If the header is configured but missing or invalid, the proxy address is kept,
 * which is never on the allowlist, so access fails closed.
 */
class UseClientIpHeader
{
    public function handle(Request $request, Closure $next)
    {
        $header = config('network.client_ip_header');

        if ($header) {
            $ip = trim((string) $request->headers->get($header));

            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                $request->server->set('REMOTE_ADDR', $ip);
            }
        }

        return $next($request);
    }
}

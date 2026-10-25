<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Browser security headers on every response (docs/SECURITY.md §3).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers  = $response->headers;

        // Don't advertise the PHP version (also set expose_php=Off in production php.ini)
        $headers->remove('X-Powered-By');
        if (! app()->runningInConsole()) header_remove('X-Powered-By');

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');   // résumé preview iframes are same-origin locally
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        $hsts = config('security.hsts_max_age');
        if ($request->isSecure() && $hsts > 0) {
            $headers->set('Strict-Transport-Security', "max-age={$hsts}");
        }

        // CSP only matters for documents; leave file downloads (PDF previews) alone
        $mode = config('security.csp');
        if ($mode !== 'off' && str_contains((string) $headers->get('Content-Type', 'text/html'), 'text/html')) {
            $name = $mode === 'report' ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy';
            $headers->set($name, $this->policy($request));
        }

        return $response;
    }

    private function policy(Request $request): string
    {
        $dev     = $this->viteDevServer();          // `npm run dev` serves scripts from another port
        $storage = $this->storageOrigins();         // S3 bucket: signed résumé links shown in an iframe

        $directives = [
            'default-src'     => ["'self'"],
            'script-src'      => ["'self'", ...$dev],
            'style-src'       => ["'self'", "'unsafe-inline'", 'https://fonts.googleapis.com', ...$dev],
            'font-src'        => ["'self'", 'https://fonts.gstatic.com', 'data:'],
            'img-src'         => ["'self'", 'data:', 'blob:', 'https:'],   // Google profile photos, bucket
            'connect-src'     => ["'self'", ...$dev, ...array_map(fn ($o) => preg_replace('#^http#', 'ws', $o), $dev)],
            'frame-src'       => ["'self'", ...$storage],
            'object-src'      => ["'none'"],
            'base-uri'        => ["'self'"],
            'form-action'     => ["'self'", 'https://accounts.google.com'],
            'frame-ancestors' => ["'self'"],
        ];

        $policy = collect($directives)->map(fn ($v, $k) => $k.' '.implode(' ', array_unique($v)))->implode('; ');

        return $request->isSecure() ? $policy.'; upgrade-insecure-requests' : $policy;
    }

    private function viteDevServer(): array
    {
        if (! Vite::isRunningHot()) return [];

        $url = rtrim(trim(File::get(Vite::hotFile())), '/');

        return [$url];
    }

    private function storageOrigins(): array
    {
        $origins = [];
        foreach (['filesystems.disks.s3.endpoint', 'filesystems.disks.s3.url'] as $key) {
            $host = parse_url((string) config($key), PHP_URL_HOST);
            if ($host) {
                // Virtual-hosted buckets live on a subdomain of the endpoint (bucket.storage.railway.app)
                $origins[] = "https://{$host}";
                $origins[] = "https://*.{$host}";
            }
        }

        return $origins;
    }
}

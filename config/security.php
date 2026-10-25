<?php

/*
 * Security settings. See docs/SECURITY.md for what each one protects against.
 */

return [

    // Content-Security-Policy mode: "enforce" (block), "report" (browser only logs
    // violations to the console, nothing is blocked) or "off".
    'csp' => env('SECURITY_CSP', 'enforce'),

    // Strict-Transport-Security max-age in seconds, sent only over HTTPS. 0 disables it.
    'hsts_max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),

    // HR and admin accounts are logged out after this many minutes without a request.
    // Applicants use SESSION_LIFETIME. 0 disables the HR idle timeout.
    'hr_idle_minutes' => (int) env('HR_IDLE_MINUTES', 30),

];

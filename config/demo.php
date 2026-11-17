<?php

/*
 * Public live demo (docs/LIVE_DEMO.md). Everything here is off unless DEMO_MODE=true,
 * so local development and production behave exactly as before.
 */

return [

    'enabled' => (bool) env('DEMO_MODE', false),

    // Shown on the login page; meets the password policy (12+ chars, mixed case, a number)
    'password' => env('DEMO_PASSWORD', 'Demo@Ihr2026!'),

    'accounts' => [
        'applicant' => ['label' => 'Applicant', 'email' => 'applicant@demo.ihr', 'username' => 'demo.applicant', 'name' => 'Nur Aisyah Kamal'],
        'hr'        => ['label' => 'HR staff', 'email' => 'hr@demo.ihr', 'username' => 'demo.hr', 'name' => 'Farah Hanim'],
    ],

    // Stricter upload rule for the public demo: PDF only, 2 MB
    'resume_max_kb' => 2048,

];

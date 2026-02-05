<?php

use App\Models\HRStaff;
use App\Models\Applicant;

return [

    'defaults' => [
        // we’ll still use 'web' for generic stuff, but our login will use hr/applicant guards explicitly
        'guard' => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users', // default (optional to keep)
        ],

        'hr' => [
            'driver' => 'session',
            'provider' => 'hr_staff',
        ],

        'applicant' => [
            'driver' => 'session',
            'provider' => 'applicants',
        ],
    ],

    'providers' => [
        'users' => [ // keep default if you still have users table; not required for this flow
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        'hr_staff' => [
            'driver' => 'eloquent',
            'model' => HRStaff::class,
        ],

        'applicants' => [
            'driver' => 'eloquent',
            'model' => Applicant::class,
        ],
    ],

    'passwords' => [
        // optional: set up brokers separately if you plan to use Forgot Password per guard
        'hr_staff' => [
            'provider' => 'hr_staff',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
        'applicants' => [
            'provider' => 'applicants',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];

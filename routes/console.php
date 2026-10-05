<?php

use Illuminate\Support\Facades\Schedule;

/*
| Scheduled tasks. They run inside the worker container (`php artisan schedule:work`,
| started by docker/start.sh), so no separate cron service is needed.
*/

// In-app reminder to applicants about interviews in the next 3 days (once per interview)
Schedule::command('app:send-interview-reminders')->hourly()->withoutOverlapping();

// Drop failed-job records older than 30 days
Schedule::command('queue:prune-failed --hours=720')->daily();

// Public test site only: back to fresh demo data every Monday 02:00 Malaysia time
Schedule::command('ihr:reset-demo')
    ->weeklyOn(1, '02:00')
    ->timezone('Asia/Kuala_Lumpur')
    ->environments(['staging']);

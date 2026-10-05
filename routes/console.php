<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduler
|--------------------------------------------------------------------------
|
| Requires one cron entry: * * * * * php artisan schedule:run
| Each command loops over gyms and runs inside that gym's tenant context.
|
*/

Schedule::command('gym:memberships:refresh')->hourly()->withoutOverlapping();
Schedule::command('gym:reminders:expiring')->dailyAt('09:00')->withoutOverlapping();
Schedule::command('gym:reminders:payment-due')->dailyAt('10:00')->withoutOverlapping();
Schedule::command('gym:reminders:classes')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('gym:reminders:maintenance')->dailyAt('08:00')->withoutOverlapping();

<?php

use App\Jobs\CheckContractExpirations;
use App\Jobs\CheckRunwayThresholds;
use App\Jobs\DispatchDailyStandups;
use App\Jobs\FetchNewsJob;
use App\Jobs\GenerateDailyInsights;
use App\Jobs\GenerateLinkedInPostsJob;
use App\Jobs\GenerateNewspaperJob;
use App\Jobs\GenerateWeeklyInsights;
use App\Jobs\SendCashBalanceReminders;
use App\Jobs\SendOverdueTaskReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| Here is where you can define your scheduled tasks.
|
*/

// Market news - every 4 hours
Schedule::job(new FetchNewsJob)->everyFourHours();

// Daily newspaper generation - at 6am (after news is fetched)
Schedule::job(new GenerateNewspaperJob)->dailyAt('06:00');

// Contract expiration checks - daily at 6am
Schedule::job(new CheckContractExpirations)->dailyAt('06:00');

// Runway threshold checks - daily at 6am
Schedule::job(new CheckRunwayThresholds)->dailyAt('06:00');

// Daily AI insights - daily at 7am (after threshold checks)
Schedule::job(new GenerateDailyInsights)->dailyAt('07:00');

// LinkedIn posts generation - daily at 7:30am (after insights)
Schedule::job(new GenerateLinkedInPostsJob)->dailyAt('07:30');

// Weekly AI insights - every Monday at 8am
Schedule::job(new GenerateWeeklyInsights)->weeklyOn(1, '08:00');

// Daily standup emails - every 15 minutes to catch user-preferred times
Schedule::job(new DispatchDailyStandups)->everyFifteenMinutes();

// Weekly cash balance reminder - every Sunday at 9am
Schedule::job(new SendCashBalanceReminders)->weeklyOn(0, '09:00');

// Overdue task reminders - every 15 minutes to catch user-preferred times
Schedule::job(new SendOverdueTaskReminders)->everyFifteenMinutes();

// Product revenue snapshots - first day of each month at 1am
Schedule::command('products:capture-snapshots')->monthlyOn(1, '01:00');

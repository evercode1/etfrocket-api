<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Intraday AI Signals
|--------------------------------------------------------------------------
|
| Runs every hour. Command itself checks market hours using
| IsMarketOpenService and exits when market is closed.
|
*/

Schedule::command('ai:generate-signals')
    ->hourly()
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Nightly Data Collection
|--------------------------------------------------------------------------
|
| Market closes at 4:00 PM ET.
| 7:00 PM ET = 23:00 UTC during DST.
|
*/

Schedule::command('securities:run-ai-price-extractions')
    ->dailyAt('23:00')
    ->withoutOverlapping();

Schedule::command('securities:run-ai-dividend-extractions')
    ->dailyAt('23:30')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Metrics Calculation
|--------------------------------------------------------------------------
|
| Allow extractions to finish first.
|
*/

Schedule::command('securities:calculate-metrics')
    ->dailyAt('02:00')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Scheduled Content Updates
|--------------------------------------------------------------------------
*/

Schedule::command('app:run-scheduled-updates')
    ->dailyAt('03:00')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Cleanup
|--------------------------------------------------------------------------
*/

Schedule::command('app:cleanup-ai-pipeline-data')
    ->dailyAt('05:15')
    ->withoutOverlapping();

Schedule::command('app:trim-cron-logs')
    ->weeklyOn(0, '04:00')
    ->withoutOverlapping();

Schedule::command('app:trim-import-logs')
    ->weeklyOn(0, '05:00')
    ->withoutOverlapping();

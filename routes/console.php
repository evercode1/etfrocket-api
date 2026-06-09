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
    ->timezone('UTC')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Nightly Data Collection
|--------------------------------------------------------------------------
|
| Market closes at 4:00 PM ET.
| 5:00 PM ET = 21:00 UTC during DST.
|
*/

Schedule::command('securities:run-ai-price-extraction')
    ->dailyAt('21:00')
    ->timezone('UTC')
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
    ->timezone('UTC')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Scheduled Content Updates
|--------------------------------------------------------------------------
*/

Schedule::command('securities:run-scheduled-updates')
    ->hourly()
    ->timezone('UTC')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Cleanup
|--------------------------------------------------------------------------
*/

Schedule::command('app:cleanup-ai-pipeline-data')
    ->dailyAt('05:15')
    ->timezone('UTC')
    ->withoutOverlapping();

Schedule::command('app:trim-cron-logs')
    ->weeklyOn(0, '04:00')
    ->timezone('UTC')
    ->withoutOverlapping();

Schedule::command('app:trim-import-logs')
    ->weeklyOn(0, '05:00')
    ->timezone('UTC')
    ->withoutOverlapping();

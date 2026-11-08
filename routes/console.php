<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Paid data sources (disabled)
|--------------------------------------------------------------------------
|
| ai:generate-signals (OpenAI) and securities:run-ai-price-extraction
| (Twelve Data) are no longer scheduled. The app now focuses on AUM data
| from the free issuer scrapers (securities:run-scheduled-updates).
| See docs/aum-pivot-plan.md.
|
*/

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

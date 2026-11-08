<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Disabled commands
|--------------------------------------------------------------------------
|
| No longer scheduled since the app now focuses on AUM data from the free
| issuer scrapers (securities:run-scheduled-updates):
|
|   ai:generate-signals                  OpenAI (paid)
|   securities:run-ai-price-extraction   Twelve Data (paid)
|   securities:calculate-metrics         only fed the retired comparison /
|                                        portfolio features; the AUM pages
|                                        compute from AUM history directly
|
| The commands still exist and can be run manually. See
| docs/aum-pivot-plan.md.
|
| When adding or removing a scheduled command here, update
| PipelineHealthQuery::EXPECTED_CRONS (a test enforces this).
|
*/

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

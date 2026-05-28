<?php

use Illuminate\Support\Facades\Schedule;

// data extraction for price and dividend

Schedule::command('etfs:run-ai-extraction')
    ->dailyAt('00:05')
    ->withoutOverlapping();

Schedule::command('etf:calculate-metrics')
    ->dailyAt('02:00')
    ->withoutOverlapping();

Schedule::command('app:trim-cron-logs')
    ->weekly()
    ->weeklyOn(

        0,

        '04:00'

    )
    ->withoutOverlapping();

Schedule::command('app:trim-import-logs')
    ->weekly()
    ->weeklyOn(

        0,

        '05:00'

    )
    ->withoutOverlapping();

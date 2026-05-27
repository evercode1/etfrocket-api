<?php

namespace App\Http\Controllers\Dev\ExternalSeeders;

use App\Models\Interval;
use App\Http\Controllers\Controller;

class IntervalsSeederController extends Controller
{
    public function run(): void
    {
        Interval::truncate();

        $intervals = [

            'everySecond',

            'everyTwoSeconds',

            'everyThreeSeconds',

            'everyTenSeconds',

            'everyFifteenSeconds',

            'everyTwentySeconds',

            'everyThirtySeconds',

            'everyMinute',

            'everyTwoMinutes',

            'everyThreeMinutes',

            'everyFourMinutes',

            'everyFiveMinutes',

            'everyTenMinutes',

            'everyFifteenMinutes',

            'Hourly',

            'HourlyAt',

            'everyOddHour',

            'everyTwoHours',

            'everyThreeHours',

            'everyFourHours',

            'everySixHours',

            'Daily',

            'DailyAt',

            'twiceDaily',

            'Weekly',

            'Monthly',

            'twiceMonthly',

            'lastDayOfMonth',

            'quarterly',

            'quarterlyOn',

            'yearly',

            'yearlyOn',

        ];

        foreach ($intervals as $intervalName) {

            Interval::create([

                'interval_name' => $intervalName,

            ]);
        }
    }
}

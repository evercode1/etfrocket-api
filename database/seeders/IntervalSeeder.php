<?php

namespace Database\Seeders;

use App\Models\Interval;
use Illuminate\Database\Seeder;

class IntervalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Interval::truncate();

        $values = [

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

        foreach (
            $values as $value
        ) {

            Interval::create([

                'interval_name' =>
                $value,

            ]);
        }
    }
}

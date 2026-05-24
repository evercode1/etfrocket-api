<?php

namespace Database\Factories;

use App\Models\CronLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class CronLogFactory extends Factory
{
    protected $model =
    CronLog::class;

    public function definition(): array
    {
        $startTime =
            $this->faker->dateTimeBetween(
                '-30 days',
                'now'
            );

        $runTime =
            $this->faker->numberBetween(
                1,
                120
            );

        $endTime =
            (clone $startTime)
            ->modify(
                "+{$runTime} seconds"
            );

        return [

            'cron_name' =>
            $this->faker->randomElement([

                'app:trim-cron-logs',

                'app:generate-ai-signals',

                'app:end-auctions',

                'app:send-won-item-notifications',

            ]),

            'status_id' =>
            $this->faker->numberBetween(
                1,
                2
            ),

            'cron_description' =>
            $this->faker->sentence(),

            'cron_fail_details' =>
            $this->faker->optional(0.25)
                ->sentence(),

            'interval_id' =>
            $this->faker->numberBetween(
                1,
                5
            ),

            'run_time' =>
            $runTime,

            'start_time' =>
            $startTime,

            'end_time' =>
            $endTime,

            'notification_status_id' =>
            $this->faker->numberBetween(
                1,
                3
            ),

        ];
    }
}

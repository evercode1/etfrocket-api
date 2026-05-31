<?php

namespace App\Console\Commands;

use App\Services\Crons\CronService;
use Illuminate\Console\Command;

class RunScheduledUpdates extends Command
{
    protected $signature =

        'securities:run-scheduled-updates

        {--day= : Override current day}

        {--hour= : Override current hour}

        {--limit= : Limit schedules processed}

        {--security_id= : Run for a specific security}';

    protected $description =

        'Runs scheduled security updates (Dividend, AUM, NAV) based on security update schedules.';

    public function handle(): void
    {
        $interval = 'Hourly';

        $payload = [

            'day' => $this->option(
                'day'
            ),

            'hour' => $this->option(
                'hour'
            ),

            'limit' => $this->option(
                'limit'
            ),

            'security_id' => $this->option(
                'security_id'
            ),

        ];

        CronService::runAndLogCron(

            $this->signature,

            $this->description,

            'RunScheduledUpdatesHandler',

            'handleRunScheduledUpdates',

            $interval,

            $payload

        );
    }
}

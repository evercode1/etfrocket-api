<?php

namespace App\Console\Commands;

use App\Services\Crons\CronService;
use Illuminate\Console\Command;

class RunScheduledUpdates extends Command
{
    protected $signature =

        'securities:run-scheduled-updates

        {--hour= : Override current hour}

        {--limit= : Limit schedules processed}

        {--security_id= : Run for a specific security}';

    protected $description =

        'Runs daily fund data updates (AUM, NAV) via issuer scrapers, based on each schedule\'s run hour.';

    public function handle(): void
    {
        $interval = 'Hourly';

        $payload = [

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

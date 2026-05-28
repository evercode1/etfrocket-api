<?php

namespace App\Console\Commands;

use App\Services\Crons\CronService;
use Illuminate\Console\Command;

class CalculateSecurityMetrics extends Command
{
    protected $signature =
        'securities:calculate-metrics
        {--symbol= : Calculate metrics for a single security symbol}';

    protected $description =
        'Calculate security performance metrics for all active securities and performance range types.';

    public function handle(): void
    {
        $interval = 'Daily';

        $payload = [

            'symbol' => $this->option(
                'symbol'
            ),

        ];

        CronService::runAndLogCron(

            $this->signature,

            $this->description,

            'CalculateSecurityMetricsHandler',

            'handleCalculateSecurityMetrics',

            $interval,

            $payload

        );
    }
}

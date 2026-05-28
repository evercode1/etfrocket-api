<?php

namespace App\Console\Commands;

use App\Services\Crons\CronService;
use Illuminate\Console\Command;

class CalculateEtfMetrics extends Command
{
    protected $signature =
        'etfs:calculate-metrics
        {--symbol= : Calculate metrics for a single ETF symbol}';

    protected $description =
        'Calculate ETF performance metrics for all active ETFs and performance range types.';

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

            'CalculateEtfMetricsHandler',

            'handleCalculateEtfMetrics',

            $interval,

            $payload

        );
    }
}

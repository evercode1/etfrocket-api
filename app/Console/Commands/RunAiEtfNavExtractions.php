<?php

namespace App\Console\Commands;

use App\Services\Crons\CronService;
use Illuminate\Console\Command;

class RunAiEtfNavExtractions extends Command
{
    protected $signature =

        'etfs:run-ai-nav-extraction

        {--symbol= : Run extraction for a single ETF symbol}

        {--limit= : Limit the number of ETFs processed}

        {--force : Force extraction even if no fresh NAV data exists}';

    protected $description =

        'Run AI ETF NAV extraction and process extracted ETF NAV data.';

    public function handle(): void
    {
        $interval = 'Weekly';

        $payload = [

            'symbol' => $this->option(
                'symbol'
            ),

            'limit' => $this->option(
                'limit'
            ),

            'force' => $this->option(
                'force'
            ),

        ];

        CronService::runAndLogCron(

            $this->signature,

            $this->description,

            'RunAiEtfNavExtractionsHandler',

            'handleRunAiEtfNavExtractions',

            $interval,

            $payload

        );
    }
}

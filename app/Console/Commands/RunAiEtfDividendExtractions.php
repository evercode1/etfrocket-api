<?php

namespace App\Console\Commands;

use App\Services\Crons\CronService;
use Illuminate\Console\Command;

class RunAiEtfDividendExtractions extends Command
{
    protected $signature =

        'etfs:run-ai-dividend-extraction

        {--symbol= : Run extraction for a single ETF symbol}

        {--limit= : Limit the number of ETFs processed}

        {--force : Force extraction even if no fresh dividend data exists}';

    protected $description =

        'Run AI ETF dividend extraction and process extracted ETF dividend data.';

    public function handle(): void
    {
        $interval = 'Daily';

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

            'RunAiEtfDividendExtractionsHandler',

            'handleRunAiEtfDividendExtractions',

            $interval,

            $payload

        );
    }
}

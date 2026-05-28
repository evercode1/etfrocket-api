<?php

namespace App\Console\Commands;

use App\Services\Crons\CronService;
use Illuminate\Console\Command;

class RunAiEtfPriceExtractions extends Command
{
    protected $signature =

        'etfs:run-ai-price-extraction

        {--symbol= : Run extraction for a single ETF symbol}

        {--limit= : Limit the number of ETFs processed}

        {--force : Force extraction even if no fresh price data exists}';

    protected $description =

        'Run AI ETF price extraction and process extracted ETF price data.';

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

            'RunAiEtfPriceExtractionsHandler',

            'handleRunAiEtfPriceExtractions',

            $interval,

            $payload

        );
    }
}

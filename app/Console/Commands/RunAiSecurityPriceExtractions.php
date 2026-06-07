<?php

namespace App\Console\Commands;

use App\Services\Crons\CronService;
use Illuminate\Console\Command;

class RunAiSecurityPriceExtractions extends Command
{
    protected $signature =

        'securities:run-ai-price-extraction

        {--symbol= : Run extraction for a single security symbol}

        {--limit= : Limit the number of securities processed}

        {--force : Force extraction even if no fresh price data exists}';

    protected $description =

        'Run AI security price extraction and process extracted security price data.';

    public function handle(): int
    {

        if (! $this->option('force')

            && now()->isWeekend()) {

            $this->info('Weekend detected. Exiting.');

            return self::SUCCESS;

        }

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

            'RunAiSecurityPriceExtractionsHandler',

            'handleRunAiSecurityPriceExtractions',

            $interval,

            $payload

        );

        return self::SUCCESS;
    }
}

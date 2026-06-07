<?php

namespace App\Console\Commands;

use App\Services\Crons\CronService;
use Illuminate\Console\Command;

class RunAiSecurityDividendExtractions extends Command
{
    protected $signature =

        'securities:run-ai-dividend-extraction

        {--symbol= : Run extraction for a single security symbol}

        {--limit= : Limit the number of securities processed}

        {--force : Force extraction even if no fresh dividend data exists}';

    protected $description =

        'Run AI security dividend extraction and process extracted security dividend data.';

    public function handle(): int
    {

        if (now()->isWeekend()) {

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

            'RunAiSecurityDividendExtractionsHandler',

            'handleRunAiSecurityDividendExtractions',

            $interval,

            $payload

        );

        return self::SUCCESS;
    }
}

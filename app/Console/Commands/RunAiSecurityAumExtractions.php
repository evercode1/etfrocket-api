<?php

namespace App\Console\Commands;

use App\Services\Crons\CronService;
use Illuminate\Console\Command;

class RunAiSecurityAumExtractions extends Command
{
    protected $signature =

        'securities:run-ai-aum-extraction

        {--symbol= : Run extraction for a single security symbol}

        {--limit= : Limit the number of securities processed}

        {--force : Force extraction even if no fresh AUM data exists}';

    protected $description =

        'Run AI security AUM extraction and process extracted security AUM data.';

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

            'RunAiSecurityAumExtractionsHandler',

            'handleRunAiSecurityAumExtractions',

            $interval,

            $payload

        );
    }
}

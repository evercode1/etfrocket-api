<?php

namespace App\Console\Commands;

use App\Services\AI\AiSignals\IsMarketOpenService;
use App\Services\Crons\CronService;
use Illuminate\Console\Command;

class GenerateAiSignalCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature =

        'ai:generate-signals

        {--force : Force signal generation even if no fresh data exists}';

    /**
     * The console command description.
     */
    protected $description =

        'Generate AI market signals';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {

        if (! app(IsMarketOpenService::class)->isOpen() && ! $this->option('force')) {

            $this->info('Market is closed. Exiting.');

            return self::SUCCESS;
        }

        $interval = 'Hourly';

        $payload = [

            'force' => $this->option(

                'force'

            ),

        ];

        CronService::runAndLogCron(

            $this->signature,

            $this->description,

            'GenerateAiSignalsHandler',

            'handleGenerateAiSignals',

            $interval,

            $payload

        );

        return self::SUCCESS;
    }
}

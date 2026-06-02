<?php

namespace App\Console\Commands;

use App\Services\Crons\CronService;
use Illuminate\Console\Command;

class CleanupAiPipelineDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature =
        'app:cleanup-ai-pipeline-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description =
        'Delete old AI pipeline records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $interval = 'Daily';

        CronService::runAndLogCron(

            $this->signature,

            $this->description,

            'CleanupAiPipelineDataHandler',

            'handleCleanupAiPipelineData',

            $interval

        );
    }
}

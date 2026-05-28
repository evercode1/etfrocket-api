<?php

namespace App\Console\Commands;

use App\Services\Crons\CronService;
use Illuminate\Console\Command;

class TrimImportLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:trim-import-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'remove old import logs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $interval = 'Weekly';

        CronService::runAndLogCron(

            $this->signature,
            $this->description,
            'trimImportLogsHandler',
            'handleTrimImportLogs',
            $interval

        );
    }
}

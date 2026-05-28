<?php

namespace App\Services\Crons\Handlers;

use App\Models\ImportLog;
use Carbon\Carbon;

class TrimImportLogsHandler
{
    // we ignore payload, but we need it for dynamic method calling in CronService

    public function handleTrimImportLogs(

        array $payload = []

    ): array {

        // Get the date one week ago

        $one_week_ago = Carbon::now()->subWeek();

        // Delete records older than one week

        try {

            ImportLog::where(

                'created_at',

                '<',

                $one_week_ago

            )->delete();

            return ['success' => 1, 'cron_fail_details' => null];
        } catch (\Exception $e) {

            // Log the exception message to the CronFailLog table

            // the specific details

            $cron_fail_details = $this->errorMessage().$e->getMessage();

            return ['success' => 0, 'cron_fail_details' => $cron_fail_details];
        }
    }

    public function errorMessage()
    {

        return 'Trim import logs failed to trim anything. ';
    }
}

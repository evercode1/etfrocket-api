<?php

namespace App\Services\Crons\Handlers;

use Illuminate\Support\Facades\DB;

class CleanupAiPipelineDataHandler
{
    // Payload is required for dynamic invocation by CronService

    public function handleCleanupAiPipelineData(

        array $payload = []

    ): array {

        try {

            $retentionHours =

                config(
                    'security_pipeline_cleanup.retention_hours'
                );

            $cutoff =

                now()->subHours(
                    $retentionHours
                );

            foreach (

                config(
                    'security_pipeline_cleanup.tables'
                ) as $table

            ) {

                DB::table($table)

                    ->where(

                        'created_at',

                        '<',

                        $cutoff

                    )

                    ->delete();
            }

            return [

                'success' => 1,

                'cron_fail_details' => null,

            ];

        } catch (\Exception $e) {

            $cron_fail_details =

                $this->errorMessage()

                .

                $e->getMessage();

            return [

                'success' => 0,

                'cron_fail_details' => $cron_fail_details,

            ];
        }
    }

    public function errorMessage(): string
    {
        return 'AI pipeline cleanup failed. ';
    }
}

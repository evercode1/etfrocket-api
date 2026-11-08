<?php

namespace App\Services\Crons\Handlers;

use App\Jobs\RunScheduledSecurityUpdateJob;
use App\Models\ImportType;
use App\Models\SecurityIngestionBatch;
use App\Models\SecurityIngestionBatchItem;
use App\Models\SecurityUpdateSchedule;
use App\Models\SecurityUpdateType;
use App\Models\Status;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class RunScheduledUpdatesHandler
{
    public function handleRunScheduledUpdates(
        array $payload = []
    ): array {

        try {

            Log::info(
                'SCHEDULED UPDATE HANDLER STARTED'
            );

            /*
            |--------------------------------------------------------------------------
            | Fund data only, every day
            |--------------------------------------------------------------------------
            |
            | Only FUND_DATA schedules (free issuer scrapers: AUM + NAV) run.
            | DIVIDEND schedules depend on the paid Twelve Data API and are
            | intentionally skipped; their rows are left untouched.
            |
            | Fund data runs daily at each schedule's run_hour. run_day is
            | ignored (it is stored as 1-7 while Carbon's dayOfWeek is 0-6,
            | so day-7 schedules previously never ran). Re-runs on the same
            | data date are idempotent (updateOrCreate on security + date).
            |
            */

            $runHour =
                $payload['hour']
                ?? now()->hour;

            $limit =
                $payload['limit']
                ?? null;

            $securityId =
                $payload['security_id']
                ?? null;

            $query =

                SecurityUpdateSchedule::query()

                    ->where(
                        'status_id',
                        Status::ACTIVE
                    )

                    ->where(
                        'security_update_type_id',
                        SecurityUpdateType::FUND_DATA
                    )

                    ->where(
                        'run_hour',
                        $runHour
                    );

            if ($securityId) {

                $query->where(
                    'security_id',
                    $securityId
                );
            }

            if ($limit) {

                $query->limit(
                    (int) $limit
                );
            }

            $schedules =

                $query

                    ->orderBy(
                        'security_id'
                    )

                    ->get();

            Log::info(

                'SCHEDULE QUERY COMPLETE',

                [

                    'run_hour' => $runHour,

                    'count' => $schedules->count(),

                ]

            );

            if (

                $schedules->isEmpty()

            ) {

                return [

                    'success' => 1,

                    'cron_fail_details' => null,

                ];
            }

            $batch =

                SecurityIngestionBatch::create([

                    'batch_uuid' => Str::uuid()->toString(),

                    'import_type_id' => ImportType::AI_DATA_EXTRACTION,

                    'status_id' => Status::PENDING,

                    'total_securities' => $schedules->count(),

                    'processed_count' => 0,

                    'success_count' => 0,

                    'failure_count' => 0,

                    'duplicate_count' => 0,

                    'passed_data_integrity_check' => false,

                    'processing_notes' => 'Scheduled fund data updates queued. '.

                        "Run hour {$runHour}. ".

                        "Total updates: {$schedules->count()}",

                    'started_at' => now(),

                ]);

            foreach (

                $schedules as $schedule

            ) {

                SecurityIngestionBatchItem::create([

                    'security_ingestion_batch_id' => $batch->id,

                    'security_update_schedule_id' => $schedule->id,

                    'security_id' => $schedule->security_id,

                    'security_update_type_id' => $schedule->security_update_type_id,

                    'status_id' => Status::PENDING,

                    'attempts' => 0,

                    'is_processed' => false,

                    'is_success' => false,

                ]);

                RunScheduledSecurityUpdateJob::dispatch(

                    $batch->id,

                    $schedule->id

                );
            }

            Log::info(
                'SCHEDULED UPDATE HANDLER COMPLETE'
            );

            return [

                'success' => 1,

                'cron_fail_details' => null,

            ];

        } catch (Throwable $e) {

            Log::error(

                'SCHEDULED UPDATE HANDLER FAILED',

                [

                    'message' => $e->getMessage(),

                    'trace' => $e->getTraceAsString(),

                ]

            );

            report($e);

            return [

                'success' => 0,

                'cron_fail_details' => $this->errorMessage().
                    $e->getMessage(),

            ];
        }
    }

    public function errorMessage(): string
    {
        return 'Scheduled update processing failed. ';
    }
}

<?php

namespace App\Services\Crons;

use App\Jobs\RunScheduledSecurityUpdateJob;
use App\Models\ImportType;
use App\Models\SecurityIngestionBatch;
use App\Models\SecurityIngestionBatchItem;
use App\Models\SecurityUpdateSchedule;
use App\Models\Status;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Fund data batch dispatcher
|--------------------------------------------------------------------------
|
| Creates an ingestion batch with one item per FUND_DATA schedule and
| queues RunScheduledSecurityUpdateJob for each. Used by the hourly
| scheduled run (RunScheduledUpdatesHandler) and by manual runs started
| from the admin Pipeline Health page, so both behave identically.
|
| Progress: the jobs increment the batch's processed / success / failure
| counts; completed_at is set when processed_count reaches total.
|
*/

class FundDataBatchDispatcher
{
    /**
     * @param  Collection<int, SecurityUpdateSchedule>  $schedules
     */
    public function dispatch(Collection $schedules, string $notes): SecurityIngestionBatch
    {
        $batch = SecurityIngestionBatch::create([
            'batch_uuid' => Str::uuid()->toString(),
            'import_type_id' => ImportType::AI_DATA_EXTRACTION,
            'status_id' => Status::PENDING,
            'total_securities' => $schedules->count(),
            'processed_count' => 0,
            'success_count' => 0,
            'failure_count' => 0,
            'duplicate_count' => 0,
            'passed_data_integrity_check' => false,
            'processing_notes' => $notes,
            'started_at' => now(),
        ]);

        foreach ($schedules as $schedule) {

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

        return $batch;
    }
}

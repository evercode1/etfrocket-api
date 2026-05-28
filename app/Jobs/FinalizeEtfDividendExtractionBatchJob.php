<?php

namespace App\Jobs;

use App\Models\EtfIngestionBatch;
use App\Models\ImportType;
use App\Models\Status;
use App\Services\ImportLogs\ImportLogsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FinalizeEtfDividendExtractionBatchJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(

        public int $batchId

    ) {}

    public function handle(): void
    {
        $batch =

            EtfIngestionBatch::findOrFail(
                $this->batchId
            );

        $passedIntegrityCheck =

            $batch->failure_count === 0

            &&

            $batch->success_count ===
            $batch->total_etfs;

        $statusId =

            $batch->failure_count > 0

            ? Status::FAILED

            : Status::COMPLETED;

        $completedAt =
            now();

        $runTime =

            $batch->started_at

            ? $batch->started_at
                ->diffInSeconds(
                    $completedAt
                )

            : 0;

        $processingNotes =

            $batch->failure_count > 0

            ? 'AI ETF dividend extraction batch completed with failures.'

            : 'AI ETF dividend extraction batch completed successfully.';

        ImportLogsService::log(

            import_type_id: ImportType::AI_DATA_EXTRACTION,

            status_id: $statusId,

            run_time: $runTime,

            rows_processed: $batch->processed_count,

            records_created: $batch->success_count,

            records_updated: 0,

            duplicate_rows: $batch->duplicate_count,

            failure_count: $batch->failure_count,

            passed_data_integrity_check: $passedIntegrityCheck,

            processing_notes: $processingNotes,

            import_fail_details: $batch->failure_count > 0

                ? $batch->import_fail_details

                : null,

            started_at: $batch->started_at,

            completed_at: $completedAt,

        );

        $batch->update([

            'status_id' => $statusId,

            'passed_data_integrity_check' => $passedIntegrityCheck,

            'processing_notes' => $processingNotes,

            'completed_at' => $completedAt,

        ]);
    }
}

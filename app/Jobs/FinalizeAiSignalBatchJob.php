<?php

namespace App\Jobs;

use App\Models\AiMarketSignal;
use App\Models\AiSignalBatch;
use App\Models\AiSignalBatchItem;
use App\Models\Status;
use App\Services\ImportLogs\ImportLogsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FinalizeAiSignalBatchJob implements ShouldQueue
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

            AiSignalBatch::findOrFail(
                $this->batchId
            );

        /*
        |--------------------------------------------------------------------------
        | Completion State
        |--------------------------------------------------------------------------
        */

        $batchCompleted =

            $batch->processed_count
            >=
            $batch->total_signals;

        /*
        |--------------------------------------------------------------------------
        | Integrity Check
        |--------------------------------------------------------------------------
        */

        $passedIntegrityCheck =

            $batch->failure_count === 0

            &&

            $batch->success_count ===
            $batch->total_signals;

        /*
        |--------------------------------------------------------------------------
        | Determine Final Status
        |--------------------------------------------------------------------------
        */

        $statusId =

            $batch->failure_count > 0

            ? Status::FAILED

            : Status::COMPLETED;

        /*
        |--------------------------------------------------------------------------
        | Runtime
        |--------------------------------------------------------------------------
        */

        $completedAt =
            now();

        $runTime =

            $batch->started_at

            ? $batch->started_at
                ->diffInSeconds(
                    $completedAt
                )

            : 0;

        /*
        |--------------------------------------------------------------------------
        | Processing Notes
        |--------------------------------------------------------------------------
        */

        $processingNotes =

            $batch->failure_count > 0

            ? 'AI signal batch completed with failures.'

            : 'AI signal batch completed successfully.';

        /*
        |--------------------------------------------------------------------------
        | Batch Items
        |--------------------------------------------------------------------------
        */

        $batchItems =

            AiSignalBatchItem::where(

                'ai_signal_batch_id',

                $batch->id

            )->get();

        /*
        |--------------------------------------------------------------------------
        | Import Logs
        |--------------------------------------------------------------------------
        */

        foreach (
            $batchItems as $batchItem
        ) {

            $generatedMarkdown = null;

            /*
            |--------------------------------------------------------------------------
            | Attempt To Load Generated Signal
            |--------------------------------------------------------------------------
            */

            $signal =

                AiMarketSignal::where(

                    'signal_type_id',

                    $batchItem->signal_type_id

                )
                    ->latest(
                        'generated_at'
                    )
                    ->first();

            if ($signal) {

                $generatedMarkdown =
                    $signal->markdown_content;
            }

            ImportLogsService::log(

                import_type_id: $batchItem->import_type_id,

                status_id: $batchItem->status_id,

                run_time: $batchItem->runtime_ms
                    ? round(
                        $batchItem->runtime_ms / 1000
                    )
                    : 0,

                rows_processed: $batchItem->is_processed
                    ? 1
                    : 0,

                records_created: $batchItem->is_success
                    ? 1
                    : 0,

                records_updated: 0,

                duplicate_rows: 0,

                failure_count: $batchItem->status_id === Status::FAILED
                    ? 1
                    : 0,

                passed_data_integrity_check: $batchItem->status_id === Status::COMPLETED,

                generated_markdown: $generatedMarkdown,

                processing_notes: $batchItem->status_id === Status::FAILED

                    ? 'AI signal generation failed.'

                    : 'AI signal generated successfully.',

                import_fail_details: $batchItem->error_message,

                started_at: $batchItem->started_at,

                completed_at: $batchItem->completed_at,

            );
        }

        /*
        |--------------------------------------------------------------------------
        | Finalize Batch
        |--------------------------------------------------------------------------
        */

        $batch->update([

            'status_id' => $statusId,

            'passed_data_integrity_check' => $passedIntegrityCheck,

            'processing_notes' => $processingNotes,

            'completed_at' => $completedAt,

        ]);
    }
}

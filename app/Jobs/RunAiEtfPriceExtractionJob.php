<?php

namespace App\Jobs;

use App\Models\Etf;
use App\Models\EtfIngestionBatch;
use App\Models\EtfIngestionBatchItem;
use App\Models\Status;
use App\Services\AI\Extractions\AiEtfPriceExtractionService;
use App\Services\AI\Extractions\ProcessAiEtfPriceExtractionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RunAiEtfPriceExtractionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(

        public int $batchId,

        public int $etfId

    ) {}

    public function handle(

        AiEtfPriceExtractionService $aiEtfPriceExtractionService,

        ProcessAiEtfPriceExtractionService $processAiEtfPriceExtractionService

    ): void {

        /*
        |--------------------------------------------------------------------------
        | Job Start
        |--------------------------------------------------------------------------
        */

        $startedAt =
            microtime(true);

        $batchItem =

            EtfIngestionBatchItem::where(

                'etf_ingestion_batch_id',

                $this->batchId

            )
                ->where(

                    'etf_id',

                    $this->etfId

                )
                ->firstOrFail();

        try {

            /*
            |--------------------------------------------------------------------------
            | Mark Processing
            |--------------------------------------------------------------------------
            */

            $batchItem->update([

                'status_id' => Status::PROCESSING,

                'attempts' => $batchItem->attempts + 1,

                'started_at' => now(),

            ]);

            Log::info('BATCH ITEM MARKED PROCESSING', [

                'batch_id' => $this->batchId,

                'etf_id' => $this->etfId,

            ]);

            /*
            |--------------------------------------------------------------------------
            | ETF Lookup
            |--------------------------------------------------------------------------
            */

            $etf =

                Etf::findOrFail(
                    $this->etfId
                );

            /*
            |--------------------------------------------------------------------------
            | AI Extraction
            |--------------------------------------------------------------------------
            */

            $extraction =

                $aiEtfPriceExtractionService
                    ->extract(
                        $etf
                    );

            /*
            |--------------------------------------------------------------------------
            | Processing
            |--------------------------------------------------------------------------
            */

            $processAiEtfPriceExtractionService
                ->process(
                    $extraction
                );

            /*
            |--------------------------------------------------------------------------
            | Runtime
            |--------------------------------------------------------------------------
            */

            $runtimeMs =
                (int) (

                    (microtime(true) - $startedAt)

                    * 1000

                );

            /*
            |--------------------------------------------------------------------------
            | Successful Completion
            |--------------------------------------------------------------------------
            */

            DB::transaction(

                function () use (

                    $batchItem,

                    $runtimeMs

                ) {

                    $batchItem->update([

                        'status_id' => Status::COMPLETED,

                        'runtime_ms' => $runtimeMs,

                        'completed_at' => now(),

                    ]);

                    EtfIngestionBatch::where(

                        'id',

                        $this->batchId

                    )

                        ->increment(

                            'processed_count'

                        );

                    EtfIngestionBatch::where(

                        'id',

                        $this->batchId

                    )

                        ->increment(

                            'success_count'

                        );
                }

            );

            /*
            |--------------------------------------------------------------------------
            | Finalization Check
            |--------------------------------------------------------------------------
            */

            $this->checkForBatchCompletion();
        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Entered Catch
            |--------------------------------------------------------------------------
            */

            /*
            |--------------------------------------------------------------------------
            | Runtime
            |--------------------------------------------------------------------------
            */

            $runtimeMs =
                (int) (

                    (microtime(true) - $startedAt)

                    * 1000

                );

            /*
            |--------------------------------------------------------------------------
            | Determine Final Status
            |--------------------------------------------------------------------------
            */

            $statusId =

                $this->attempts() >= $this->tries

                ? Status::FAILED

                : Status::PENDING;

            /*
            |--------------------------------------------------------------------------
            | Failed Attempt
            |--------------------------------------------------------------------------
            */

            DB::transaction(

                function () use (

                    $batchItem,

                    $runtimeMs,

                    $statusId,

                    $e

                ) {

                    $batchItem->update([

                        'status_id' => $statusId,

                        'runtime_ms' => $runtimeMs,

                        'error_message' => $e->getMessage(),

                        'completed_at' => $statusId === Status::FAILED

                            ? now()

                            : null,

                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Only Final Failure Counts
                    |--------------------------------------------------------------------------
                    */

                    if (

                        $statusId ===
                        Status::FAILED

                    ) {

                        EtfIngestionBatch::where(

                            'id',

                            $this->batchId

                        )

                            ->increment(

                                'processed_count'

                            );

                        EtfIngestionBatch::where(

                            'id',

                            $this->batchId

                        )

                            ->increment(

                                'failure_count'

                            );
                    }
                }

            );

            /*
            |--------------------------------------------------------------------------
            | Finalization Check
            |--------------------------------------------------------------------------
            */

            if (

                $statusId ===
                Status::FAILED

            ) {

                $this->checkForBatchCompletion();
            }

            throw $e;
        }
    }

    private function checkForBatchCompletion(): void
    {

        $batch =

            EtfIngestionBatch::findOrFail(
                $this->batchId
            );

        /*
        |--------------------------------------------------------------------------
        | Batch Complete
        |--------------------------------------------------------------------------
        */

        if (

            $batch->processed_count >=
            $batch->total_etfs

        ) {

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Finalizers
            |--------------------------------------------------------------------------
            */

            $updated =

                EtfIngestionBatch::where(

                    'id',

                    $batch->id

                )
                    ->whereNull(

                        'completed_at'

                    )
                    ->update([

                        'completed_at' => now(),

                    ]);

            if ($updated) {

                FinalizeEtfPriceExtractionBatchJob::dispatch(

                    $batch->id

                );
            }
        }
    }
}

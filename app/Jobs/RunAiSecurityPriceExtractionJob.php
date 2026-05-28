<?php

namespace App\Jobs;

use App\Models\Security;
use App\Models\SecurityIngestionBatch;
use App\Models\SecurityIngestionBatchItem;
use App\Models\Status;
use App\Services\AI\Extractions\AiSecurityPriceExtractionService;
use App\Services\AI\Extractions\ProcessAiSecurityPriceExtractionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RunAiSecurityPriceExtractionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(

        public int $batchId,

        public int $securityId

    ) {}

    public function handle(

        AiSecurityPriceExtractionService $aiSecurityPriceExtractionService,

        ProcessAiSecurityPriceExtractionService $processAiSecurityPriceExtractionService

    ): void {

        /*
        |--------------------------------------------------------------------------
        | Job Start
        |--------------------------------------------------------------------------
        */

        $startedAt =
            microtime(true);

        $batchItem =

            SecurityIngestionBatchItem::where(

                'security_ingestion_batch_id',

                $this->batchId

            )
                ->where(

                    'security_id',
                    $this->securityId

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

                'security_id' => $this->securityId,

            ]);

            /*
            |--------------------------------------------------------------------------
            | Security Lookup
            |--------------------------------------------------------------------------
            */

            $security =

                Security::findOrFail(
                    $this->securityId
                );

            /*
            |--------------------------------------------------------------------------
            | AI Extraction
            |--------------------------------------------------------------------------
            */

            $extraction =

                $aiSecurityPriceExtractionService
                    ->extract(
                        $security
                    );

            /*
            |--------------------------------------------------------------------------
            | Processing
            |--------------------------------------------------------------------------
            */

            $processAiSecurityPriceExtractionService
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

                    SecurityIngestionBatch::where(

                        'id',

                        $this->batchId

                    )

                        ->increment(

                            'processed_count'

                        );

                    SecurityIngestionBatch::where(

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

                        SecurityIngestionBatch::where(

                            'id',

                            $this->batchId

                        )

                            ->increment(

                                'processed_count'

                            );

                        SecurityIngestionBatch::where(

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

            SecurityIngestionBatch::findOrFail(
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

                SecurityIngestionBatch::where(

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

                FinalizeSecurityPriceExtractionBatchJob::dispatch(

                    $batch->id

                );
            }
        }
    }
}

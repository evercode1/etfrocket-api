<?php

namespace App\Jobs;

use App\Models\AiSignalBatch;
use App\Models\AiSignalBatchItem;
use App\Models\Status;
use App\Services\AI\AiSignals\GenerateAiSignalService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateAiSignalJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(

        public int $batchId,

        public int $signalTypeId,

        public int $importTypeId,

        public bool $force = false

    ) {}

    public function handle(

        GenerateAiSignalService $generateAiSignalService

    ): void {

        /*
        |--------------------------------------------------------------------------
        | Job Start
        |--------------------------------------------------------------------------
        */

        Log::info('AI SIGNAL JOB START', [

            'batch_id' => $this->batchId,

            'signal_type_id' => $this->signalTypeId,

            'attempt' => $this->attempts(),

        ]);

        $startedAt =
            microtime(true);

        $batchItem =

            AiSignalBatchItem::where(

                'ai_signal_batch_id',

                $this->batchId

            )
                ->where(

                    'signal_type_id',

                    $this->signalTypeId

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

            Log::info('AI SIGNAL BATCH ITEM PROCESSING', [

                'batch_id' => $this->batchId,

                'signal_type_id' => $this->signalTypeId,

            ]);

            /*
            |--------------------------------------------------------------------------
            | Generate Signal
            |--------------------------------------------------------------------------
            */

            $signal =

                $generateAiSignalService
                    ->generate(

                        $this->signalTypeId

                    );

            Log::info('AI SIGNAL GENERATED', [

                'batch_id' => $this->batchId,

                'signal_type_id' => $this->signalTypeId,

                'signal_id' => $signal->id ?? null,

            ]);

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

                        'is_processed' => true,

                        'is_success' => true,

                        'completed_at' => now(),

                    ]);

                    AiSignalBatch::where(

                        'id',

                        $this->batchId

                    )

                        ->increment(
                            'processed_count'
                        );

                    AiSignalBatch::where(

                        'id',

                        $this->batchId

                    )

                        ->increment(
                            'success_count'
                        );
                }

            );

            Log::info('AI SIGNAL BATCH COUNTS UPDATED', [

                'batch_id' => $this->batchId,

                'signal_type_id' => $this->signalTypeId,

            ]);

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

            Log::error('AI SIGNAL JOB FAILED', [

                'batch_id' => $this->batchId,

                'signal_type_id' => $this->signalTypeId,

                'attempt' => $this->attempts(),

                'message' => $e->getMessage(),

            ]);

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

                        'is_processed' => $statusId === Status::FAILED,

                        'is_success' => false,

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

                        AiSignalBatch::where(

                            'id',

                            $this->batchId

                        )

                            ->increment(
                                'processed_count'
                            );

                        AiSignalBatch::where(

                            'id',

                            $this->batchId

                        )

                            ->increment(
                                'failure_count'
                            );
                    }
                }

            );

            Log::error('AI SIGNAL FAILURE STATE SAVED', [

                'batch_id' => $this->batchId,

                'signal_type_id' => $this->signalTypeId,

            ]);

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
        Log::info('CHECKING AI SIGNAL BATCH COMPLETION', [

            'batch_id' => $this->batchId,

        ]);

        $batch =

            AiSignalBatch::findOrFail(
                $this->batchId
            );

        /*
        |--------------------------------------------------------------------------
        | Batch Complete
        |--------------------------------------------------------------------------
        */

        if (

            $batch->processed_count >=
            $batch->total_signals

        ) {

            Log::info('AI SIGNAL BATCH COMPLETE', [

                'batch_id' => $this->batchId,

                'processed_count' => $batch->processed_count,

                'total_signals' => $batch->total_signals,

            ]);

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Finalizers
            |--------------------------------------------------------------------------
            */

            $updated =

                AiSignalBatch::where(

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

                Log::info('AI SIGNAL FINALIZER DISPATCHED', [

                    'batch_id' => $this->batchId,

                ]);

                FinalizeAiSignalBatchJob::dispatch(

                    $batch->id

                );
            }
        }
    }
}

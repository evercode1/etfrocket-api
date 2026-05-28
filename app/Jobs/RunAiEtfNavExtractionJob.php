<?php

namespace App\Jobs;

use App\Models\Security;
use App\Models\SecurityIngestionBatch;
use App\Models\SecurityIngestionBatchItem;
use App\Models\Status;
use App\Services\AI\Extractions\AiEtfNavExtractionService;
use App\Services\AI\Extractions\ProcessAiEtfNavExtractionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RunAiEtfNavExtractionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(

        public int $batchId,

        public int $securityId

    ) {}

    public function handle(

        AiEtfNavExtractionService $aiEtfNavExtractionService,

        ProcessAiEtfNavExtractionService $processAiEtfNavExtractionService

    ): void {

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

            $batchItem->update([

                'status_id' => Status::PROCESSING,

                'attempts' => $batchItem->attempts + 1,

                'started_at' => now(),

            ]);

            $security =
                Security::findOrFail(
                    $this->securityId
                );

            $extraction =

                $aiEtfNavExtractionService
                    ->extract(
                        $security
                    );

            $processAiEtfNavExtractionService
                ->process(
                    $extraction
                );

            $runtimeMs =
                (int) (

                    (microtime(true) - $startedAt)

                    * 1000

                );

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

                    )->increment(
                        'processed_count'
                    );

                    SecurityIngestionBatch::where(

                        'id',

                        $this->batchId

                    )->increment(
                        'success_count'
                    );
                }

            );

            $this->checkForBatchCompletion();
        } catch (\Throwable $e) {

            $runtimeMs =
                (int) (

                    (microtime(true) - $startedAt)

                    * 1000

                );

            $statusId =

                $this->attempts() >= $this->tries

                ? Status::FAILED

                : Status::PENDING;

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

                    if (

                        $statusId ===
                        Status::FAILED

                    ) {

                        SecurityIngestionBatch::where(

                            'id',

                            $this->batchId

                        )->increment(
                            'processed_count'
                        );

                        SecurityIngestionBatch::where(

                            'id',

                            $this->batchId

                        )->increment(
                            'failure_count'
                        );
                    }
                }

            );

            Log::error(

                'AI ETF NAV extraction job failed.',

                [

                    'batch_id' => $this->batchId,

                    'security_id' => $this->securityId,

                    'message' => $e->getMessage(),

                ]

            );

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

        if (

            $batch->processed_count >=
            $batch->total_securities

        ) {

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

                FinalizeEtfNavExtractionBatchJob::dispatch(

                    $batch->id

                );
            }
        }
    }
}

<?php

namespace App\Jobs;

use App\Jobs\FinalizeEtfAumExtractionBatchJob;
use App\Models\Etf;
use App\Models\EtfIngestionBatch;
use App\Models\EtfIngestionBatchItem;
use App\Models\Status;
use App\Services\AI\Extractions\AiEtfAumExtractionService;
use App\Services\AI\Extractions\ProcessAiEtfAumExtractionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RunAiEtfAumExtractionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(

        public int $batchId,

        public int $etfId

    ) {}

    public function handle(

        AiEtfAumExtractionService
        $aiEtfAumExtractionService,

        ProcessAiEtfAumExtractionService
        $processAiEtfAumExtractionService

    ): void {

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

            $batchItem->update([

                'status_id' =>
                Status::PROCESSING,

                'attempts' =>
                $batchItem->attempts + 1,

                'started_at' =>
                now(),

            ]);

            $etf =
                Etf::findOrFail(
                    $this->etfId
                );

            $extraction =

                $aiEtfAumExtractionService
                ->extract(
                    $etf
                );

            $processAiEtfAumExtractionService
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

                        'status_id' =>
                        Status::COMPLETED,

                        'runtime_ms' =>
                        $runtimeMs,

                        'completed_at' =>
                        now(),

                    ]);

                    EtfIngestionBatch::where(

                        'id',

                        $this->batchId

                    )->increment(
                        'processed_count'
                    );

                    EtfIngestionBatch::where(

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

                        'status_id' =>
                        $statusId,

                        'runtime_ms' =>
                        $runtimeMs,

                        'error_message' =>
                        $e->getMessage(),

                        'completed_at' =>

                        $statusId === Status::FAILED

                            ? now()

                            : null,

                    ]);

                    if (

                        $statusId ===
                        Status::FAILED

                    ) {

                        EtfIngestionBatch::where(

                            'id',

                            $this->batchId

                        )->increment(
                            'processed_count'
                        );

                        EtfIngestionBatch::where(

                            'id',

                            $this->batchId

                        )->increment(
                            'failure_count'
                        );
                    }
                }

            );

            Log::error(

                'AI ETF AUM extraction job failed.',

                [

                    'batch_id' =>
                    $this->batchId,

                    'etf_id' =>
                    $this->etfId,

                    'message' =>
                    $e->getMessage(),

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

            EtfIngestionBatch::findOrFail(
                $this->batchId
            );

        if (

            $batch->processed_count >=
            $batch->total_etfs

        ) {

            $updated =

                EtfIngestionBatch::where(

                    'id',

                    $batch->id

                )

                ->whereNull(
                    'completed_at'
                )

                ->update([

                    'completed_at' =>
                    now(),

                ]);

            if ($updated) {

                FinalizeEtfAumExtractionBatchJob::dispatch(

                    $batch->id

                );
            }
        }
    }
}

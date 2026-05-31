<?php

namespace App\Jobs;

use App\Models\SecurityIngestionBatch;
use App\Models\SecurityIngestionBatchItem;
use App\Models\SecurityUpdateSchedule;
use App\Models\SecurityUpdateType;
use App\Models\Status;
use App\Services\AI\Extractions\AiSecurityAumExtractionService;
use App\Services\AI\Extractions\AiSecurityDividendExtractionService;
use App\Services\AI\Extractions\AiSecurityNavExtractionService;
use App\Services\AI\Extractions\ProcessAiSecurityAumExtractionService;
use App\Services\AI\Extractions\ProcessAiSecurityDividendExtractionService;
use App\Services\AI\Extractions\ProcessAiSecurityNavExtractionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RunScheduledSecurityUpdateJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public int $batchId,
        public int $scheduleId
    ) {}

    public function handle(

        AiSecurityDividendExtractionService $dividendExtractionService,
        ProcessAiSecurityDividendExtractionService $processDividendService,

        AiSecurityAumExtractionService $aumExtractionService,
        ProcessAiSecurityAumExtractionService $processAumService,

        AiSecurityNavExtractionService $navExtractionService,
        ProcessAiSecurityNavExtractionService $processNavService

    ): void {

        Log::info('SCHEDULED UPDATE JOB START', [

            'batch_id' => $this->batchId,

            'schedule_id' => $this->scheduleId,

            'attempt' => $this->attempts(),

        ]);

        $startedAt =
            microtime(true);

        $schedule =

            SecurityUpdateSchedule::findOrFail(
                $this->scheduleId
            );

        $batchItem =

            SecurityIngestionBatchItem::where(

                'security_ingestion_batch_id',

                $this->batchId

            )
                ->where(

                    'security_update_schedule_id',

                    $this->scheduleId

                )
                ->firstOrFail();

        try {

            $batchItem->update([

                'status_id' => Status::PROCESSING,

                'attempts' => $batchItem->attempts + 1,

                'started_at' => now(),

            ]);

            $security =
                $schedule->security;

            switch (

                $schedule->security_update_type_id

            ) {

                case SecurityUpdateType::DIVIDEND:

                    $extraction =

                        $dividendExtractionService
                            ->extract(
                                $security
                            );

                    $processDividendService
                        ->process(
                            $extraction
                        );

                    break;

                case SecurityUpdateType::AUM:

                    $extraction =

                        $aumExtractionService
                            ->extract(
                                $security
                            );

                    $processAumService
                        ->process(
                            $extraction
                        );

                    break;

                case SecurityUpdateType::NAV:

                    $extraction =

                        $navExtractionService
                            ->extract(
                                $security
                            );

                    $processNavService
                        ->process(
                            $extraction
                        );

                    break;

                default:

                    throw new \RuntimeException(
                        'Unknown security update type: '.

                        $schedule->security_update_type_id

                    );
            }

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

                        'is_processed' => true,

                        'is_success' => true,

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

                        $statusId === Status::FAILED

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

                'Scheduled security update failed',

                [

                    'batch_id' => $this->batchId,

                    'schedule_id' => $this->scheduleId,

                    'message' => $e->getMessage(),

                ]

            );

            if (

                $statusId === Status::FAILED

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

            FinalizeScheduledUpdatesBatchJob::dispatch(
                $batch->id
            );
        }
    }
}

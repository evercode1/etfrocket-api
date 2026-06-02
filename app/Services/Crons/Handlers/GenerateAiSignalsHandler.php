<?php

namespace App\Services\Crons\Handlers;

use App\Jobs\GenerateAiSignalJob;
use App\Models\AiMarketSignal;
use App\Models\AiSignalBatch;
use App\Models\AiSignalBatchItem;
use App\Models\ImportType;
use App\Models\SignalType;
use App\Models\Status;
use App\Services\AI\AiSignals\IsMarketOpenService;
use App\Services\ImportLogs\ImportLogsService;
use Illuminate\Support\Str;
use Throwable;

class GenerateAiSignalsHandler
{
    public function __construct(

        private IsMarketOpenService $isMarketOpenService

    ) {}

    public function handleGenerateAiSignals(
        array $payload = []
    ): array {

        $startedAt =
            now();

        try {

            $force =

                $payload['force']
                ?? false;

            /*
            |--------------------------------------------------------------------------
            | Market Open Check
            |--------------------------------------------------------------------------
            */

            if (

                ! $force &&

                ! $this->isMarketOpenService
                    ->isOpen()

            ) {

                $message =

                    'Skipped AI signal generation. Market is currently closed.';

                ImportLogsService::log(

                    import_type_id: ImportType::MARKET_SNAPSHOT,

                    status_id: Status::COMPLETED,

                    run_time: $startedAt->diffInSeconds(
                        now()
                    ),

                    rows_processed: 0,

                    records_created: 0,

                    records_updated: 0,

                    duplicate_rows: 0,

                    failure_count: 0,

                    passed_data_integrity_check: true,

                    processing_notes: $message,

                    started_at: $startedAt,

                    completed_at: now(),

                );

                return [

                    'success' => 1,

                    'cron_fail_details' => null,

                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Freshness Check
            |--------------------------------------------------------------------------
            */

            $latestSignalDate =

                optional(

                    AiMarketSignal::latest(
                        'generated_at'
                    )->first()

                )?->generated_at?->toDateString();

            $today =
                now()->toDateString();

            if (

                ! $force &&

                $latestSignalDate ===
                $today

            ) {

                $message =

                    'Skipped AI signal generation. Signals already generated today.';

                ImportLogsService::log(

                    import_type_id: ImportType::MARKET_SNAPSHOT,

                    status_id: Status::COMPLETED,

                    run_time: $startedAt->diffInSeconds(
                        now()
                    ),

                    rows_processed: 0,

                    records_created: 0,

                    records_updated: 0,

                    duplicate_rows: 0,

                    failure_count: 0,

                    passed_data_integrity_check: true,

                    processing_notes: $message,

                    started_at: $startedAt,

                    completed_at: now(),

                );

                return [

                    'success' => 1,

                    'cron_fail_details' => null,

                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Signal Types
            |--------------------------------------------------------------------------
            */

            $signalTypes = [

                SignalType::MARKET_SNAPSHOT => ImportType::MARKET_SNAPSHOT,

                SignalType::MARKET_CONDITIONS => ImportType::MARKET_CONDITIONS,

                SignalType::ETF_WATCHLIST => ImportType::ETF_WATCHLIST,

                // SignalType::MARKET_EVENTS => ImportType::MARKET_EVENTS,

            ];

            /*
            |--------------------------------------------------------------------------
            | Create Batch
            |--------------------------------------------------------------------------
            */

            $batch =
                AiSignalBatch::create([

                    'batch_uuid' => Str::uuid()->toString(),

                    'status_id' => Status::PENDING,

                    'total_signals' => count($signalTypes),

                    'processed_count' => 0,

                    'success_count' => 0,

                    'failure_count' => 0,

                    'passed_data_integrity_check' => false,

                    'processing_notes' => $force

                        ? 'Forced AI signal batch queued.'

                        : 'AI signal batch queued.',

                    'started_at' => now(),

                ]);

            /*
            |--------------------------------------------------------------------------
            | Create Batch Items + Dispatch Jobs
            |--------------------------------------------------------------------------
            */

            foreach (

                $signalTypes as $signalTypeId => $importTypeId

            ) {

                AiSignalBatchItem::create([

                    'ai_signal_batch_id' => $batch->id,

                    'signal_type_id' => $signalTypeId,

                    'import_type_id' => $importTypeId,

                    'status_id' => Status::PENDING,

                    'attempts' => 0,

                    'is_processed' => false,

                    'is_success' => false,

                ]);

                GenerateAiSignalJob::dispatch(

                    $batch->id,

                    $signalTypeId,

                    $importTypeId,

                    $force

                );
            }

            return [

                'success' => 1,

                'cron_fail_details' => null,

            ];
        } catch (Throwable $e) {

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
        return 'AI signal generation failed. ';
    }
}

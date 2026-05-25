<?php

namespace App\Services\Crons\Handlers;

use App\Models\AiDataExtraction;

use App\Models\Etf;

use App\Models\EtfPriceHistory;

use App\Models\ImportType;

use App\Models\Status;

use App\Services\AI\Extractions\AiEtfDataExtractionService;

use App\Services\AI\Extractions\ProcessAiEtfDataExtractionService;

use App\Services\ImportLogs\ImportLogsService;

use Illuminate\Support\Facades\Log;

use Throwable;

class RunAiEtfDataExtractionsHandler

{

    public function __construct(

        private AiEtfDataExtractionService

        $aiEtfDataExtractionService,

        private ProcessAiEtfDataExtractionService

        $processAiEtfDataExtractionService

    ) {}

    public function handleRunAiEtfDataExtractions(

        array $payload = []

    ): array {

        $startedAt = now();

        try {

            $symbol =

                $payload['symbol']

                ?? null;

            $limit =

                $payload['limit']

                ?? null;

            $force =

                $payload['force']

                ?? false;

            /*

            |--------------------------------------------------------------------------

            | Freshness Check

            |--------------------------------------------------------------------------

            */

            $latestPriceDate =

                EtfPriceHistory::max(

                    'price_date'

                );

            $latestExtractionDate =

                optional(

                    AiDataExtraction::latest(

                        'created_at'

                    )->first()

                )?->created_at?->toDateString();

            if (

                ! $force &&

                $latestPriceDate &&

                $latestExtractionDate &&

                $latestPriceDate ===

                $latestExtractionDate

            ) {

                $message =

                    'Skipped AI ETF extraction. No fresh ETF price data detected.';

                ImportLogsService::log(

                    import_type_id: ImportType::AI_DATA_EXTRACTION,

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

            | ETF Query

            |--------------------------------------------------------------------------

            */

            $query =

                Etf::query()

                ->orderBy(

                    'symbol'

                );

            if ($symbol) {

                $query->where(

                    'symbol',

                    strtoupper(

                        $symbol

                    )

                );
            }

            if ($limit) {

                $query->limit(

                    (int) $limit

                );
            }

            $etfs =

                $query->get();

            if (

                $etfs->isEmpty()

            ) {

                return [

                    'success' => 1,

                    'cron_fail_details' => null,

                ];
            }

            /*

            |--------------------------------------------------------------------------

            | Aggregate Metrics

            |--------------------------------------------------------------------------

            */

            $rowsProcessed = 0;

            $recordsCreated = 0;

            $failureCount = 0;

            /*

            |--------------------------------------------------------------------------

            | Processing Loop

            |--------------------------------------------------------------------------

            */

            foreach (

                $etfs as $etf

            ) {

                $rowsProcessed++;

                try {

                    $extraction =

                        $this->aiEtfDataExtractionService

                        ->extract(

                            $etf

                        );

                    $this->processAiEtfDataExtractionService

                        ->process(

                            $extraction

                        );

                    $recordsCreated++;
                } catch (Throwable $e) {

                    $failureCount++;

                    Log::error(

                        'AI ETF extraction command failed for ETF.',

                        [

                            'etf_id' =>

                            $etf->id,

                            'symbol' =>

                            $etf->symbol,

                            'message' =>

                            $e->getMessage(),

                            'exception' =>

                            $e,

                        ]

                    );
                }
            }

            /*

            |--------------------------------------------------------------------------

            | Import Log

            |--------------------------------------------------------------------------

            */

            ImportLogsService::log(

                import_type_id: ImportType::AI_DATA_EXTRACTION,

                status_id: $failureCount > 0

                    ? Status::FAILED

                    : Status::COMPLETED,

                run_time: $startedAt->diffInSeconds(

                    now()

                ),

                rows_processed: $rowsProcessed,

                records_created: $recordsCreated,

                records_updated: 0,

                duplicate_rows: 0,

                failure_count: $failureCount,

                passed_data_integrity_check: $failureCount === 0,

                processing_notes: $force

                    ? 'Forced AI ETF extraction executed successfully.'

                    : 'AI ETF extraction completed successfully.',

                started_at: $startedAt,

                completed_at: now(),

            );

            return [

                'success' => 1,

                'cron_fail_details' => null,

            ];
        } catch (Throwable $e) {

            report($e);

            ImportLogsService::log(

                import_type_id: ImportType::AI_DATA_EXTRACTION,

                status_id: Status::FAILED,

                run_time: $startedAt->diffInSeconds(

                    now()

                ),

                rows_processed: 0,

                records_created: 0,

                records_updated: 0,

                duplicate_rows: 0,

                failure_count: 1,

                passed_data_integrity_check: false,

                processing_notes: 'AI ETF extraction failed.',

                import_fail_details: $e->getMessage(),

                started_at: $startedAt,

                completed_at: now(),

            );

            return [

                'success' => 0,

                'cron_fail_details' =>

                $this->errorMessage()

                    . $e->getMessage(),

            ];
        }
    }

    public function errorMessage(): string

    {

        return

            'AI ETF extraction failed. ';
    }
}

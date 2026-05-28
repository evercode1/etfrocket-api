<?php

namespace App\Services\Crons\Handlers;

use App\Jobs\RunAiEtfAumExtractionJob;
use App\Models\Etf;
use App\Models\EtfAumHistory;
use App\Models\EtfIngestionBatch;
use App\Models\EtfIngestionBatchItem;
use App\Models\ImportType;
use App\Models\Status;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class RunAiEtfAumExtractionsHandler
{
    public function handleRunAiEtfAumExtractions(
        array $payload = []
    ): array {

        try {

            Log::info('AUM EXTRACTION HANDLER STARTED');

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

            $today =
                now()->toDateString();

            $totalEtfCount =

                Etf::where(
                    'status_id',
                    Status::ACTIVE
                )
                    ->count();

            $updatedEtfCount =

                EtfAumHistory::whereDate(
                    'retrieved_at',
                    $today
                )
                    ->distinct('etf_id')
                    ->count('etf_id');

            Log::info('AUM FRESHNESS CHECK', [

                'today' => $today,

                'total_etf_count' => $totalEtfCount,

                'updated_etf_count' => $updatedEtfCount,

            ]);

            if (

                ! $force &&

                $updatedEtfCount >=
                $totalEtfCount

            ) {

                Log::warning(
                    'AUM EXTRACTION SKIPPED - ALL ETFS ALREADY UPDATED'
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
                    ->where(
                        'status_id',
                        Status::ACTIVE
                    )
                    ->orderBy('symbol');

            if ($symbol) {

                $query->where(
                    'symbol',
                    strtoupper($symbol)
                );
            }

            if ($limit) {

                $query->limit(
                    (int) $limit
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Exclude Already Updated ETFs
            |--------------------------------------------------------------------------
            */

            if (! $force) {

                $query->whereNotIn(

                    'id',

                    EtfAumHistory::whereDate(
                        'retrieved_at',
                        $today
                    )

                        ->pluck('etf_id')

                );
            }

            $etfs =
                $query->get();

            Log::info('AUM ETF QUERY COMPLETE', [

                'count' => $etfs->count(),

            ]);

            if (
                $etfs->isEmpty()
            ) {

                Log::warning(
                    'NO ETFS FOUND FOR AUM PROCESSING'
                );

                return [

                    'success' => 1,

                    'cron_fail_details' => null,

                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Create Batch
            |--------------------------------------------------------------------------
            */

            $batch =
                EtfIngestionBatch::create([

                    'batch_uuid' => Str::uuid()->toString(),

                    'import_type_id' => ImportType::AI_DATA_EXTRACTION,

                    'status_id' => Status::PENDING,

                    'total_etfs' => $etfs->count(),

                    'processed_count' => 0,

                    'success_count' => 0,

                    'failure_count' => 0,

                    'duplicate_count' => 0,

                    'passed_data_integrity_check' => false,

                    'processing_notes' => $force

                        ? 'Forced AI ETF AUM extraction batch queued.'

                        : 'AI ETF AUM extraction batch queued.',

                    'started_at' => now(),

                ]);

            /*
            |--------------------------------------------------------------------------
            | Create Batch Items + Dispatch Jobs
            |--------------------------------------------------------------------------
            */

            foreach (
                $etfs as $etf
            ) {

                EtfIngestionBatchItem::create([

                    'etf_ingestion_batch_id' => $batch->id,

                    'etf_id' => $etf->id,

                    'status_id' => Status::PENDING,

                    'attempts' => 0,

                    'is_processed' => false,

                    'is_success' => false,

                ]);

                RunAiEtfAumExtractionJob::dispatch(

                    $batch->id,

                    $etf->id

                );
            }

            Log::info('AUM EXTRACTION HANDLER COMPLETE');

            return [

                'success' => 1,

                'cron_fail_details' => null,

            ];
        } catch (Throwable $e) {

            Log::error(

                'AUM EXTRACTION HANDLER FAILED',

                [

                    'message' => $e->getMessage(),

                    'trace' => $e->getTraceAsString(),

                ]

            );

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
        return 'AI ETF AUM extraction failed. ';
    }
}

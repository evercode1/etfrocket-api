<?php

namespace App\Services\Crons\Handlers;

use App\Jobs\RunAiEtfPriceExtractionJob;
use App\Models\AiDataExtraction;
use App\Models\Etf;
use App\Models\EtfIngestionBatch;
use App\Models\EtfIngestionBatchItem;
use App\Models\EtfPriceHistory;
use App\Models\ImportType;
use App\Models\Status;
use Illuminate\Support\Str;
use Throwable;

class RunAiEtfPriceExtractionsHandler
{
    public function handleRunAiEtfPriceExtractions(
        array $payload = []
    ): array {

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

                ->orderBy('symbol');

            if ($symbol) {

                $query->where('symbol', strtoupper($symbol));
            }

            if ($limit) {

                $query->limit((int) $limit);
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
            | Create Batch
            |--------------------------------------------------------------------------
            */

            $batch =
                EtfIngestionBatch::create([

                    'batch_uuid' => Str::uuid()->toString(),

                    'import_type_id' => ImportType::AI_DATA_EXTRACTION,

                    'status_id' => Status::PENDING,

                    'total_etfs' =>  $etfs->count(),

                    'processed_count' => 0,

                    'success_count' => 0,

                    'failure_count' => 0,

                    'duplicate_count' => 0,

                    'passed_data_integrity_check' => false,

                    'processing_notes' =>
                    $force

                        ? 'Forced AI ETF price extraction batch queued.'

                        : 'AI ETF price extraction batch queued.',

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

                RunAiEtfPriceExtractionJob::dispatch(

                    $batch->id,

                    $etf->id

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

                'cron_fail_details' =>

                $this->errorMessage() . $e->getMessage(),

            ];
        }
    }

    public function errorMessage(): string
    {
        return
            'AI ETF price extraction failed. ';
    }
}

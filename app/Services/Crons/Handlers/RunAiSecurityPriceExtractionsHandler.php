<?php

namespace App\Services\Crons\Handlers;

use App\Jobs\RunAiSecurityPriceExtractionJob;
use App\Models\ImportType;
use App\Models\Security;
use App\Models\SecurityIngestionBatch;
use App\Models\SecurityIngestionBatchItem;
use App\Models\SecurityPriceHistory;
use App\Models\Status;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class RunAiSecurityPriceExtractionsHandler
{
    public function handleRunAiSecurityPriceExtractions(
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

            $today =
                now()->toDateString();

            $totalSecurityCount =

                Security::where(
                    'status_id',
                    Status::ACTIVE
                )
                    ->count();

            $updatedSecurityCount =

                SecurityPriceHistory::where(
                    'price_date',
                    $today
                )
                    ->distinct('security_id')
                    ->count('security_id');

            if (

                ! $force &&

                $updatedSecurityCount >=
                $totalSecurityCount

            ) {

                return [

                    'success' => 1,

                    'cron_fail_details' => null,

                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Security Query
            |--------------------------------------------------------------------------
            */

            $query =
                Security::query()
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
            | Exclude Already Updated Securities
            |--------------------------------------------------------------------------
            */

            if (! $force) {

                $query->whereNotIn(

                    'id',

                    SecurityPriceHistory::where(
                        'price_date',
                        $today
                    )

                        ->pluck('security_id')

                );
            }

            $securities =
                $query->get();

            if (
                $securities->isEmpty()
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
                SecurityIngestionBatch::create([

                    'batch_uuid' => Str::uuid()->toString(),

                    'import_type_id' => ImportType::AI_DATA_EXTRACTION,

                    'status_id' => Status::PENDING,

                    'total_securities' => $securities->count(),

                    'processed_count' => 0,

                    'success_count' => 0,

                    'failure_count' => 0,

                    'duplicate_count' => 0,

                    'passed_data_integrity_check' => false,

                    'processing_notes' => $force

                        ? 'Forced AI security price extraction batch queued.'

                        : 'AI security price extraction batch queued.',

                    'started_at' => now(),

                ]);

            /*
            |--------------------------------------------------------------------------
            | Create Batch Items + Dispatch Jobs
            |--------------------------------------------------------------------------
            */

            foreach (
                $securities as $security
            ) {

                SecurityIngestionBatchItem::create([

                    'security_ingestion_batch_id' => $batch->id,

                    'security_id' => $security->id,

                    'status_id' => Status::PENDING,

                    'attempts' => 0,

                    'is_processed' => false,

                    'is_success' => false,

                ]);

                RunAiSecurityPriceExtractionJob::dispatch(

                    $batch->id,

                    $security->id

                );
            }

            return [

                'success' => 1,

                'cron_fail_details' => null,

            ];
        } catch (Throwable $e) {

            Log::error(

                'PRICE EXTRACTION HANDLER FAILED',

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
        return 'AI security price extraction failed. ';
    }
}

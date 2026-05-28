<?php

namespace App\Services\Crons\Handlers;

use App\Jobs\RunAiSecurityNavExtractionJob;
use App\Models\ImportType;
use App\Models\Security;
use App\Models\SecurityIngestionBatch;
use App\Models\SecurityIngestionBatchItem;
use App\Models\SecurityNavHistory;
use App\Models\Status;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class RunAiSecurityNavExtractionsHandler
{
    public function handleRunAiSecurityNavExtractions(
        array $payload = []
    ): array {

        try {

            Log::info('NAV EXTRACTION HANDLER STARTED');

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

                SecurityNavHistory::whereDate(
                    'retrieved_at',
                    $today
                )
                    ->distinct('security_id')
                    ->count('security_id');

            Log::info('NAV FRESHNESS CHECK', [

                'today' => $today,

                'total_security_count' => $totalSecurityCount,

                'updated_security_count' => $updatedSecurityCount,

            ]);

            if (

                ! $force &&

                $updatedSecurityCount >=
                $totalSecurityCount

            ) {

                Log::warning(
                    'NAV EXTRACTION SKIPPED - ALL SECURITIES ALREADY UPDATED'
                );

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

                    SecurityNavHistory::whereDate(
                        'retrieved_at',
                        $today
                    )

                        ->pluck('security_id')

                );
            }

            $securities =
                $query->get();

            Log::info('NAV SECURITY QUERY COMPLETE', [

                'count' => $securities->count(),

            ]);

            if (
                $securities->isEmpty()
            ) {

                Log::warning(
                    'NO SECURITIES FOUND FOR NAV PROCESSING'
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
                        ? 'Forced AI Security NAV extraction batch queued.'
                        : 'AI Security NAV extraction batch queued.',

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

                RunAiSecurityNavExtractionJob::dispatch(

                    $batch->id,

                    $security->id

                );
            }

            Log::info('NAV EXTRACTION HANDLER COMPLETE');

            return [

                'success' => 1,

                'cron_fail_details' => null,

            ];
        } catch (Throwable $e) {

            Log::error(

                'NAV EXTRACTION HANDLER FAILED',

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
        return 'AI Security NAV extraction failed. ';
    }
}

<?php

namespace App\Services\Crons\Handlers;

use App\Models\ImportType;
use App\Models\PerformanceRangeType;
use App\Models\Security;
use App\Models\SecurityMetric;
use App\Models\SecurityPriceHistory;
use App\Models\Status;
use App\Services\ImportLogs\ImportLogsService;
use App\Services\SecurityMetrics\CalculateSecurityMetricService;
use Throwable;

class CalculateSecurityMetricsHandler
{
    public function __construct(

        private CalculateSecurityMetricService $calculateSecurityMetricService

    ) {}

    public function handleCalculateSecurityMetrics(

        array $payload = []

    ): array {

        $startedAt = now();

        try {

            $force =

                $payload['force']

                ?? false;

            $symbol =

                $payload['symbol']

                ?? null;

            /*

            |--------------------------------------------------------------------------

            | Freshness Check

            |--------------------------------------------------------------------------

            */

            $latestPriceDate =

                SecurityPriceHistory::max(

                    'price_date'

                );

            $latestMetricEndDate =

                SecurityMetric::max(

                    'end_date'

                );

            if (

                ! $force &&

                $latestPriceDate &&

                $latestMetricEndDate &&

                $latestPriceDate ===

                $latestMetricEndDate

            ) {

                $message =

                    'Skipped ETF metric calculation. No fresh ETF price data detected.';

                ImportLogsService::log(

                    import_type_id: ImportType::CALCULATE_ETF_METRICS,

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

            | Load ETFs

            |--------------------------------------------------------------------------

            */

            $securities =

                Security::where(

                    'status_id',

                    Status::ACTIVE

                )
                    ->when(

                        $symbol,

                        function (

                            $query

                        ) use (

                            $symbol

                        ) {

                            $query->where(

                                'symbol',

                                strtoupper(

                                    $symbol

                                )

                            );
                        }

                    )
                    ->orderBy(

                        'symbol'

                    )
                    ->get();

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

            | Load Range Types

            |--------------------------------------------------------------------------

            */

            $rangeTypes =

                PerformanceRangeType::orderBy(

                    'id'

                )->get();

            if (

                $rangeTypes->isEmpty()

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

            $recordsUpdated = 0;

            $failureCount = 0;

            /*

            |--------------------------------------------------------------------------

            | Calculate Metrics

            |--------------------------------------------------------------------------

            */

            foreach (

                $securities as $security

            ) {

                foreach (

                    $rangeTypes as $rangeType

                ) {

                    $rowsProcessed++;

                    $metric =

                        $this->calculateSecurityMetricService
                            ->calculate(

                                $security,

                                $rangeType->id

                            );

                    if ($metric) {

                        $recordsUpdated++;
                    } else {

                        $failureCount++;
                    }
                }
            }

            /*

            |--------------------------------------------------------------------------

            | Import Log

            |--------------------------------------------------------------------------

            */

            ImportLogsService::log(

                import_type_id: ImportType::CALCULATE_ETF_METRICS,

                status_id: $failureCount > 0

                    ? Status::FAILED

                    : Status::COMPLETED,

                run_time: $startedAt->diffInSeconds(

                    now()

                ),

                rows_processed: $rowsProcessed,

                records_created: 0,

                records_updated: $recordsUpdated,

                duplicate_rows: 0,

                failure_count: $failureCount,

                passed_data_integrity_check: $failureCount === 0,

                processing_notes: $force

                    ? 'Forced security metric recalculation executed successfully.'

                    : 'Security metrics calculated successfully.',

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

                import_type_id: ImportType::CALCULATE_ETF_METRICS,

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

                processing_notes: 'Security metric calculation failed.',

                import_fail_details: $e->getMessage(),

                started_at: $startedAt,

                completed_at: now(),

            );

            return [

                'success' => 0,

                'cron_fail_details' => $this->errorMessage().

                    $e->getMessage(),

            ];
        }
    }

    public function errorMessage(): string
    {

        return 'Security metric calculation failed. ';
    }
}

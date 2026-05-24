<?php

namespace App\Services\Crons\Handlers;

use App\Models\Etf;
use App\Models\PerformanceRangeType;
use App\Models\Status;
use App\Services\EtfMetrics\CalculateEtfMetricService;
use Throwable;

class CalculateEtfMetricsHandler
{
    public function __construct(

        private CalculateEtfMetricService
        $calculateEtfMetricService

    ) {}

    public function handleCalculateEtfMetrics(
        array $payload = []
    ): array {

        try {

            $symbol =

                $payload['symbol']
                ?? null;

            $etfs =

                Etf::where(
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
                $etfs->isEmpty()
            ) {

                return [

                    'success' => 1,

                    'cron_fail_details' =>
                    null,

                ];
            }

            $rangeTypes =

                PerformanceRangeType::orderBy(
                    'id'
                )->get();

            if (
                $rangeTypes->isEmpty()
            ) {

                return [

                    'success' => 1,

                    'cron_fail_details' =>
                    null,

                ];
            }

            foreach (
                $etfs
                as $etf
            ) {

                foreach (
                    $rangeTypes
                    as $rangeType
                ) {

                    $this->calculateEtfMetricService
                        ->calculate(

                            $etf,

                            $rangeType->id

                        );
                }
            }

            return [

                'success' => 1,

                'cron_fail_details' =>
                null,

            ];
        } catch (Throwable $e) {

            report($e);

            return [

                'success' => 0,

                'cron_fail_details' =>

                $this->errorMessage() .

                    $e->getMessage(),

            ];
        }
    }

    public function errorMessage(): string
    {
        return
            'ETF metric calculation failed. ';
    }
}

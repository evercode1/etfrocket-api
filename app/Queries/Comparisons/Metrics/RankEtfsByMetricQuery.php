<?php

namespace App\Queries\Comparisons\Metrics;

use App\Models\EtfMetric;
use App\Models\PerformanceRangeType;
use Illuminate\Support\Facades\DB;

class RankEtfsByMetricQuery
{
    public function getData(
        string $metric,
        string $range,
        array $metricConfig,
        string $sortDirection = 'desc',
        int $limit = 100
    ): array {

        $metricColumn =
            $metricConfig['metric_column'];

        $performanceRangeTypeId =
            $this->resolveRangeType($range);

        $rows = EtfMetric::query()

            ->select([

                'etf_metrics.etf_id',

                'etfs.symbol',

                'etfs.fund_name',

                DB::raw("
                    {$metricColumn}
                    as metric_value
                "),

                'etf_metrics.total_return_percentage',

                'etf_metrics.aum_change_percentage',

                'etf_metrics.nav_erosion_percentage',

            ])

            ->join(
                'etfs',
                'etfs.id',
                '=',
                'etf_metrics.etf_id'
            )

            ->where(
                'etf_metrics.performance_range_type_id',
                $performanceRangeTypeId
            )

            ->whereNotNull($metricColumn)

            ->orderBy(
                $metricColumn,
                $sortDirection
            )

            ->limit($limit)

            ->get()

            ->values();

        return $rows->map(function (
            $row,
            $index
        ) use (
            $metric,
            $metricConfig
        ) {

            return [

                'rank' => $index + 1,

                'etf_id' => $row->etf_id,

                'symbol' => $row->symbol,

                'fund_name' => $row->fund_name,

                'metric' => $metric,

                'metric_label' =>
                $metricConfig['label'],

                'metric_value' =>
                $row->metric_value,

                'nav_health' =>
                $this->resolveNavHealth(
                    $row->nav_erosion_percentage
                ),

                'aum_change_percentage' =>
                $row->aum_change_percentage,

                'total_return_percentage' =>
                $row->total_return_percentage,

            ];
        })

            ->toArray();
    }

    private function resolveRangeType(
        string $range
    ): int {

        return match ($range) {

            '5d' =>
            PerformanceRangeType::FIVE_DAY,

            '30d' =>
            PerformanceRangeType::THIRTY_DAY,

            '90d' =>
            PerformanceRangeType::NINETY_DAY,

            '1y' =>
            PerformanceRangeType::ONE_YEAR,

            'max' =>
            PerformanceRangeType::MAX,

            default =>
            PerformanceRangeType::NINETY_DAY,
        };
    }

    private function resolveNavHealth(
        ?float $navErosionPercentage
    ): string {

        if (
            is_null($navErosionPercentage)
        ) {
            return 'Unknown';
        }

        if (
            $navErosionPercentage >= 0
        ) {
            return 'Stable';
        }

        if (
            $navErosionPercentage <= -10
        ) {
            return 'Watch';
        }

        return 'Mixed';
    }
}

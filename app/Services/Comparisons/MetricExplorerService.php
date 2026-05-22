<?php

namespace App\Services\Comparisons;

use App\Queries\Comparisons\Metrics\RankEtfsByMetricQuery;
use InvalidArgumentException;

class MetricExplorerService
{
    public function __construct(

        private RankEtfsByMetricQuery $rankEtfsByMetricQuery

    ) {}

    public function getData(
        ?string $metric = null,
        ?string $range = null,
        ?string $sortDirection = null,
        ?int $limit = null
    ): array {

        $metric = $this->resolveMetric(
            $metric
        );

        $range = $this->resolveRange(
            $range
        );

        $sortDirection =
            $this->resolveSortDirection(
                $sortDirection
            );

        $limit = $this->resolveLimit(
            $limit
        );

        $metricConfig =
            $this->getMetricConfig(
                $metric
            );

        $rows =
            $this->rankEtfsByMetricQuery
            ->getData(

                metric: $metric,

                range: $range,

                metricConfig: $metricConfig,

                sortDirection: $sortDirection,

                limit: $limit,

            );

        return [

            'summary' => [

                'metric' => $metric,

                'range' => $range,

                'sort_direction' =>
                $sortDirection,

                'results_count' =>
                count($rows),

            ],

            'spotlight' => array_slice(
                $rows,
                0,
                3
            ),

            'table_rows' => $rows,

            'options' => [

                'metrics' =>
                $this->getMetricOptions(),

                'ranges' =>
                $this->getRangeOptions(),

            ],

        ];
    }

    private function getMetricConfig(
        string $metric
    ): array {

        $metrics =
            config('etf_metrics.metrics');

        if (
            !array_key_exists(
                $metric,
                $metrics
            )
        ) {
            throw new InvalidArgumentException(
                "Invalid metric [{$metric}]."
            );
        }

        return $metrics[$metric];
    }

    private function resolveMetric(
        ?string $metric
    ): string {

        $metric =
            $metric ?: 'price_growth';

        $this->getMetricConfig(
            $metric
        );

        return $metric;
    }

    private function resolveRange(
        ?string $range
    ): string {

        $ranges =
            config('etf_comparison.ranges');

        $range =
            $range ?: '90d';

        if (
            !array_key_exists(
                $range,
                $ranges
            )
        ) {
            throw new InvalidArgumentException(
                "Invalid range [{$range}]."
            );
        }

        return $range;
    }

    private function resolveSortDirection(
        ?string $sortDirection
    ): string {

        $sortDirection =
            strtolower(
                $sortDirection ?: 'desc'
            );

        if (
            !in_array(
                $sortDirection,
                ['asc', 'desc']
            )
        ) {
            throw new InvalidArgumentException(
                "Invalid sort direction [{$sortDirection}]."
            );
        }

        return $sortDirection;
    }

    private function resolveLimit(
        ?int $limit
    ): int {

        $limit = $limit ?: 100;

        if ($limit < 1) {
            return 1;
        }

        if ($limit > 100) {
            return 100;
        }

        return $limit;
    }

    private function getMetricOptions(): array
    {
        return collect(
            config('etf_metrics.metrics')
        )

            ->map(function (
                $config,
                $key
            ) {

                return [

                    'value' => $key,

                    'label' =>
                    $config['label'],

                ];
            })

            ->values()

            ->toArray();
    }

    private function getRangeOptions(): array
    {
        return collect(
            config('etf_comparison.ranges')
        )

            ->map(function (
                $value,
                $key
            ) {

                return [

                    'value' => $key,

                    'label' =>
                    strtoupper($key),

                ];
            })

            ->values()

            ->toArray();
    }
}

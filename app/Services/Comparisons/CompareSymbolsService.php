<?php

namespace App\Services\Comparisons;

use App\Models\Etf;
use App\Models\EtfMetric;
use App\Models\EtfPriceHistory;
use App\Models\PerformanceRangeType;

class CompareSymbolsService
{
    public function getData(
        array $symbols,
        string $metric = 'price',
        string $range = '90d'
    ): array {

        $requestedSymbols = collect($symbols)

            ->map(fn($symbol) => strtoupper(trim($symbol)))

            ->filter()

            ->unique()

            ->values();

        $etfs = Etf::whereIn('symbol', $requestedSymbols)

            ->get();

        $foundSymbols = $etfs->pluck('symbol');

        $invalidSymbols = $requestedSymbols

            ->diff($foundSymbols)

            ->values()

            ->toArray();

        $performanceRangeTypeId = $this->resolveRangeType($range);

        $tableRows = $etfs->map(function ($etf) use (
            $performanceRangeTypeId,
            $metric,
            $range
        ) {

            $metricRecord = EtfMetric::where('etf_id', $etf->id)

                ->where(
                    'performance_range_type_id',
                    $performanceRangeTypeId
                )

                ->first();

            $latestPrice = EtfPriceHistory::where('etf_id', $etf->id)

                ->latest('price_date')

                ->first();

            return [

                'etf_id' => $etf->id,

                'symbol' => $etf->symbol,

                'fund_name' => $etf->fund_name,

                'selected_metric' => $metric,

                'selected_range' => $range,

                'latest_price' => optional($latestPrice)->close_price,

                'nav_health' => $this->resolveNavHealth($metricRecord),

                'aum_change_percentage' =>
                optional($metricRecord)->aum_change_percentage,

                'total_return_percentage' =>
                optional($metricRecord)->total_return_percentage,

                'nav_erosion_percentage' =>
                optional($metricRecord)->nav_erosion_percentage,

                'price_change_percentage' =>
                optional($metricRecord)->price_change_percentage,

                'chart_value' => $this->resolveChartValue(
                    metric: $metric,
                    latestPrice: optional($latestPrice)->close_price,
                    metricRecord: $metricRecord
                ),

            ];
        })

            ->values()

            ->toArray();

        return [

            'summary' => [

                'compared_etfs_count' => count($tableRows),

                'selected_metric' => $metric,

                'selected_range' => $range,

            ],

            'invalid_symbols' => $invalidSymbols,

            'table_rows' => $tableRows,

            'chart_rows' => [],

            'options' => [

                'metrics' => [

                    [

                        'label' => 'Price',

                        'value' => 'price',

                    ],

                    [

                        'label' => 'Monthly Income',

                        'value' => 'income',

                    ],

                    [

                        'label' => 'Total Return',

                        'value' => 'return',

                    ],

                    [

                        'label' => 'AUM Flow',

                        'value' => 'aum',

                    ],

                    [

                        'label' => 'NAV Erosion',

                        'value' => 'nav',

                    ],

                ],

                'ranges' => [

                    [

                        'label' => '5D',

                        'value' => '5d',

                    ],

                    [

                        'label' => '30D',

                        'value' => '30d',

                    ],

                    [

                        'label' => '90D',

                        'value' => '90d',

                    ],

                    [

                        'label' => '1Y',

                        'value' => '1y',

                    ],

                    [

                        'label' => 'MAX',

                        'value' => 'max',

                    ],

                ],

            ],

        ];
    }

    private function resolveRangeType(string $range): int
    {
        return match ($range) {

            '5d' => PerformanceRangeType::FIVE_DAY,

            '30d' => PerformanceRangeType::THIRTY_DAY,

            '90d' => PerformanceRangeType::NINETY_DAY,

            '1y' => PerformanceRangeType::ONE_YEAR,

            'max' => PerformanceRangeType::MAX,

            default => PerformanceRangeType::NINETY_DAY,
        };
    }

    private function resolveChartValue(
        string $metric,
        mixed $latestPrice,
        ?EtfMetric $metricRecord
    ): mixed {

        return match ($metric) {

            'return' =>
            optional($metricRecord)->total_return_percentage,

            'aum' =>
            optional($metricRecord)->aum_change_percentage,

            'nav' =>
            optional($metricRecord)->nav_erosion_percentage,

            'income' =>
            optional($metricRecord)->dividends_paid,

            default =>
            $latestPrice,
        };
    }

    private function resolveNavHealth(?EtfMetric $metricRecord): string
    {
        if (!$metricRecord) {
            return 'Unknown';
        }

        $nav = (float) ($metricRecord->nav_erosion_percentage ?? 0);

        if ($nav >= 0) {
            return 'Stable';
        }

        if ($nav <= -10) {
            return 'Watch';
        }

        return 'Mixed';
    }
}

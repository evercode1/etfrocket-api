<?php

namespace App\Services\Comparisons;

use App\Models\PerformanceRangeType;
use App\Models\Security;
use App\Models\SecurityMetric;
use App\Models\SecurityPriceHistory;
use App\Queries\Comparisons\SymbolAumHistoryChartQuery;
use App\Queries\Comparisons\SymbolDividendHistoryChartQuery;
use App\Queries\Comparisons\SymbolNavHistoryChartQuery;
use App\Queries\Comparisons\SymbolPriceHistoryChartQuery;
use App\Queries\Comparisons\SymbolTotalReturnHistoryChartQuery;

class CompareSymbolsService
{
    public function __construct(

        private SymbolPriceHistoryChartQuery $priceHistoryChartQuery,

        private SymbolDividendHistoryChartQuery $dividendHistoryChartQuery,

        private SymbolNavHistoryChartQuery $navHistoryChartQuery,

        private SymbolAumHistoryChartQuery $aumHistoryChartQuery,

        private SymbolTotalReturnHistoryChartQuery $totalReturnHistoryChartQuery,

    ) {}

    public function getData(
        array $symbols,
        string $metric = 'price',
        string $range = '90d'
    ): array {

        $requestedSymbols = collect($symbols)

            ->map(fn ($symbol) => strtoupper(trim($symbol)))

            ->filter()

            ->unique()

            ->values();

        $securities = Security::whereIn('symbol', $requestedSymbols)

            ->get();

        $foundSymbols = $securities->pluck('symbol');

        $invalidSymbols = $requestedSymbols

            ->diff($foundSymbols)

            ->values()

            ->toArray();

        $performanceRangeTypeId = $this->resolveRangeType($range);

        $tableRows = $securities->map(function ($security) use (
            $performanceRangeTypeId,
            $metric,
            $range
        ) {

            $metricRecord = SecurityMetric::where('security_id', $security->id)

                ->where(
                    'performance_range_type_id',
                    $performanceRangeTypeId
                )

                ->first();

            $latestPrice = SecurityPriceHistory::where('security_id', $security->id)

                ->latest('price_date')

                ->first();

            return [

                'security_id' => $security->id,

                'symbol' => $security->symbol,

                'security_name' => $security->detail->security_name,

                'selected_metric' => $metric,

                'selected_range' => $range,

                'latest_price' => optional($latestPrice)->close_price,

                'nav_health' => $this->resolveNavHealth($metricRecord),

                'aum_change_percentage' => optional($metricRecord)->aum_change_percentage,

                'total_return_percentage' => optional($metricRecord)->total_return_percentage,

                'nav_erosion_percentage' => optional($metricRecord)->nav_erosion_percentage,

                'price_change_percentage' => optional($metricRecord)->price_change_percentage,

                'chart_value' => $this->resolveChartValue(
                    metric: $metric,
                    latestPrice: optional($latestPrice)->close_price,
                    metricRecord: $metricRecord
                ),

            ];
        })

            ->values()

            ->toArray();

        $chartRows = $this->resolveChartRows(

            metric: $metric,

            securityIds: $securities->pluck('id')->toArray(),

            startDate: $this->resolveStartDate($range)

        );

        return [

            'summary' => [

                'compared_securities_count' => count($tableRows),

                'selected_metric' => $metric,

                'selected_range' => $range,

            ],

            'invalid_symbols' => $invalidSymbols,

            'table_rows' => $tableRows,

            'chart_rows' => $chartRows,

            'options' => [

                'metrics' => [

                    [

                        'label' => 'Price',

                        'value' => 'price',

                    ],

                    [

                        'label' => 'Dividend Amount',

                        'value' => 'dividend',

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

    private function resolveChartRows(
        string $metric,
        array $securityIds,
        string $startDate
    ): array {

        return match ($metric) {

            'dividend' => $this->dividendHistoryChartQuery->getData(
                securityIds: $securityIds,
                startDate: $startDate
            ),

            'return' => $this->totalReturnHistoryChartQuery->getData(
                securityIds: $securityIds,
                startDate: $startDate
            ),

            'nav' => $this->navHistoryChartQuery->getData(
                securityIds: $securityIds,
                startDate: $startDate
            ),

            'aum' => $this->aumHistoryChartQuery->getData(
                securityIds: $securityIds,
                startDate: $startDate
            ),

            'price' => $this->priceHistoryChartQuery->getData(
                securityIds: $securityIds,
                startDate: $startDate
            ),

            default => $this->priceHistoryChartQuery->getData(
                securityIds: $securityIds,
                startDate: $startDate
            ),
        };
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
        ?SecurityMetric $metricRecord
    ): mixed {

        return match ($metric) {

            'return' => optional($metricRecord)->total_return_percentage,

            'aum' => optional($metricRecord)->aum_change_percentage,

            'nav' => optional($metricRecord)->nav_erosion_percentage,

            'income' => optional($metricRecord)->dividends_paid,

            default => $latestPrice,
        };
    }

    private function resolveNavHealth(?SecurityMetric $metricRecord): string
    {
        if (! $metricRecord) {
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

    private function resolveStartDate(string $range): string
    {
        return match ($range) {

            '5d' => now()->subDays(5)->toDateString(),

            '30d' => now()->subDays(30)->toDateString(),

            '90d' => now()->subDays(90)->toDateString(),

            '1y' => now()->subYear()->toDateString(),

            'max' => '1900-01-01',

            default => now()->subDays(90)->toDateString(),
        };
    }
}

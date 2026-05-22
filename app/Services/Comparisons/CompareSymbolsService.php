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

        $symbols = collect($symbols)

            ->map(fn($symbol) => strtoupper(trim($symbol)))

            ->filter()

            ->unique()

            ->values()

            ->toArray();

        $etfs = Etf::whereIn('symbol', $symbols)

            ->get();

        $tableRows = $etfs->map(function ($etf) {

            $metric30 = EtfMetric::where('etf_id', $etf->id)

                ->where(
                    'performance_range_type_id',
                    PerformanceRangeType::THIRTY_DAY
                )

                ->first();

            $metric90 = EtfMetric::where('etf_id', $etf->id)

                ->where(
                    'performance_range_type_id',
                    PerformanceRangeType::NINETY_DAY
                )

                ->first();

            $latestPrice = EtfPriceHistory::where('etf_id', $etf->id)

                ->latest('price_date')

                ->first();

            return [

                'etf_id' => $etf->id,

                'symbol' => $etf->symbol,

                'fund_name' => $etf->fund_name,

                'latest_price' => optional($latestPrice)->close_price,

                'nav_health' => optional($metric30)->nav_direction_id,

                'aum_change_percentage_30_day' =>
                optional($metric30)->aum_change_percentage,

                'total_return_percentage_90_day' =>
                optional($metric90)->total_return_percentage,

            ];
        })

            ->values()

            ->toArray();

        return [

            'summary' => [

                'compared_etfs_count' => count($tableRows),

            ],

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

                ],

            ],

        ];
    }
}

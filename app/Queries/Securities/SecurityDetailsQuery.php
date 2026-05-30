<?php

namespace App\Queries\Securities;

use Illuminate\Support\Facades\Cache;

class SecurityDetailsQuery
{
    public function getData(
        string $symbol,
        int $performanceRangeTypeId,
        string $startDate
    ): array {

        $symbol = strtoupper($symbol);

        return Cache::remember(

            "security_details_{$symbol}_{$performanceRangeTypeId}_{$startDate}",

            now()->addHours(6),

            function () use (
                $symbol,
                $performanceRangeTypeId,
                $startDate
            ) {

                $security = app(
                    SecuritySummaryQuery::class
                )->getData($symbol);

                return [

                    'security' => $security,

                    'metrics' => app(
                        SecurityMetricsQuery::class
                    )->getData(

                        $security['id'],

                        $performanceRangeTypeId

                    ),

                    'chart_rows' => app(
                        SecurityChartQuery::class
                    )->getData(

                        $security['id'],

                        $startDate

                    ),

                    'signals' => app(
                        SecuritySignalsQuery::class
                    )->getData(
                        $security['id']
                    ),

                    'dividend_history' => app(
                        SecurityDividendHistoryQuery::class
                    )->getData(

                        $security['id']

                    ),

                ];
            }

        );
    }
}

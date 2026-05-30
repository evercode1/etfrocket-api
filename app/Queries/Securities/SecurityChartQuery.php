<?php

namespace App\Queries\Securities;

use App\Queries\Comparisons\SymbolAumHistoryChartQuery;
use App\Queries\Comparisons\SymbolDividendHistoryChartQuery;
use App\Queries\Comparisons\SymbolNavHistoryChartQuery;
use App\Queries\Comparisons\SymbolPriceHistoryChartQuery;

class SecurityChartQuery
{
    public function __construct(

        private SymbolPriceHistoryChartQuery $priceHistoryChartQuery,

        private SymbolNavHistoryChartQuery $navHistoryChartQuery,

        private SymbolAumHistoryChartQuery $aumHistoryChartQuery,

        private SymbolDividendHistoryChartQuery $dividendHistoryChartQuery

    ) {}

    public function getData(
        int $securityId,
        string $startDate
    ): array {

        $priceRows = $this->priceHistoryChartQuery
            ->getData(
                [$securityId],
                $startDate
            );

        $navRows = $this->navHistoryChartQuery
            ->getData(
                [$securityId],
                $startDate
            );

        $aumRows = $this->aumHistoryChartQuery
            ->getData(
                [$securityId],
                $startDate
            );

        $dividendRows = $this->dividendHistoryChartQuery
            ->getData(
                [$securityId],
                $startDate
            );

        $chartRows = [];

        foreach ($priceRows as $row) {

            $date = $row['date'];

            $chartRows[$date] = [

                'date' => $date,

                'price' => collect($row)
                    ->except('date')
                    ->first(),

                'nav' => null,

                'aum' => null,

                'dividend' => null,

            ];
        }

        foreach ($navRows as $row) {

            $date = $row['date'];

            $chartRows[$date] ??= [
                'date' => $date,
                'price' => null,
                'nav' => null,
                'aum' => null,
                'dividend' => null,
            ];

            $chartRows[$date]['nav'] =
                collect($row)
                    ->except('date')
                    ->first();
        }

        foreach ($aumRows as $row) {

            $date = $row['date'];

            $chartRows[$date] ??= [
                'date' => $date,
                'price' => null,
                'nav' => null,
                'aum' => null,
                'dividend' => null,
            ];

            $chartRows[$date]['aum'] =
                collect($row)
                    ->except('date')
                    ->first();
        }

        foreach ($dividendRows as $row) {

            $date = $row['date'];

            $chartRows[$date] ??= [
                'date' => $date,
                'price' => null,
                'nav' => null,
                'aum' => null,
                'dividend' => null,
            ];

            $chartRows[$date]['dividend'] =
                collect($row)
                    ->except('date')
                    ->first();
        }

        ksort($chartRows);

        return array_values(
            $chartRows
        );
    }
}

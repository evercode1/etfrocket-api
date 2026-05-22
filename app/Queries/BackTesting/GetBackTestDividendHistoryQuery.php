<?php

namespace App\Queries\BackTesting;

use App\Models\EtfDividendHistory;

class GetBackTestDividendHistoryQuery
{
    public function getData(
        int $etfId,
        string $startDate,
        string $endDate
    ): array {

        return EtfDividendHistory::query()

            ->select([

                'ex_dividend_date',

                'dividend_amount',

            ])

            ->where(
                'etf_id',
                $etfId
            )

            ->whereBetween(
                'ex_dividend_date',
                [
                    $startDate,
                    $endDate,
                ]
            )

            ->orderBy('ex_dividend_date')

            ->get()

            ->map(function ($row) {

                return [

                    'date' =>
                    $row->ex_dividend_date
                        ->toDateString(),

                    'dividend' =>
                    (float)
                    $row->dividend_amount,

                ];
            })

            ->toArray();
    }
}

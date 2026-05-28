<?php

namespace App\Queries\BackTesting;

use App\Models\SecurityDividendHistory;

class GetBackTestDividendHistoryQuery
{
    public function getData(
        int $securityId,
        string $startDate,
        string $endDate
    ): array {

        return SecurityDividendHistory::query()

            ->select([

                'ex_dividend_date',

                'dividend_amount',

            ])

            ->where(
                'security_id',
                $securityId
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

                    'date' => $row->ex_dividend_date
                        ->toDateString(),

                    'dividend' => (float)
                    $row->dividend_amount,

                ];
            })

            ->toArray();
    }
}

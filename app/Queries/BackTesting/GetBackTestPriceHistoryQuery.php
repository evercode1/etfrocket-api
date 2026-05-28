<?php

namespace App\Queries\BackTesting;

use App\Models\SecurityPriceHistory;

class GetBackTestPriceHistoryQuery
{
    public function getData(
        int $securityId,
        string $startDate,
        string $endDate
    ): array {

        return SecurityPriceHistory::query()

            ->select([

                'price_date',

                'close_price',

            ])

            ->where(
                'security_id',
                $securityId
            )

            ->whereBetween(
                'price_date',
                [
                    $startDate,
                    $endDate,
                ]
            )

            ->orderBy('price_date')

            ->get()

            ->map(function ($row) {

                return [

                    'date' => $row->price_date
                        ->toDateString(),

                    'price' => (float)
                    $row->close_price,

                ];
            })

            ->toArray();
    }
}

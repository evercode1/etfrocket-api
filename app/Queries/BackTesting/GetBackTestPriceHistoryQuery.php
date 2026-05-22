<?php

namespace App\Queries\BackTesting;

use App\Models\EtfPriceHistory;

class GetBackTestPriceHistoryQuery
{
    public function getData(
        int $etfId,
        string $startDate,
        string $endDate
    ): array {

        return EtfPriceHistory::query()

            ->select([

                'price_date',

                'close_price',

            ])

            ->where(
                'etf_id',
                $etfId
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

                    'date' =>
                    $row->price_date
                        ->toDateString(),

                    'price' =>
                    (float)
                    $row->close_price,

                ];
            })

            ->toArray();
    }
}

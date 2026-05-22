<?php

namespace App\Queries\Comparisons;

use App\Models\Etf;
use App\Models\EtfPriceHistory;

class SymbolPriceHistoryChartQuery
{
    public function getData(
        array $etfIds,
        string $startDate
    ): array {

        $etfs = Etf::whereIn('id', $etfIds)

            ->get()

            ->keyBy('id');

        $priceHistories = EtfPriceHistory::whereIn(
            'etf_id',
            $etfIds
        )

            ->where('price_date', '>=', $startDate)

            ->orderBy('price_date')

            ->get();

        $groupedByDate = [];

        foreach ($priceHistories as $history) {

            $symbol = $etfs

                ->get($history->etf_id)

                ?->symbol;

            if (!$symbol) {
                continue;
            }

            $date = $history->price_date->toDateString();

            if (!isset($groupedByDate[$date])) {

                $groupedByDate[$date] = [

                    'date' => $date,

                ];
            }

            $groupedByDate[$date][$symbol] =
                (float) $history->close_price;
        }

        return array_values($groupedByDate);
    }
}

<?php

namespace App\Queries\Comparisons;

use App\Models\Security;
use App\Models\SecurityPriceHistory;

class SymbolPriceHistoryChartQuery
{
    public function getData(
        array $securityIds,
        string $startDate
    ): array {

        $securities = Security::whereIn('id', $securityIds)

            ->get()

            ->keyBy('id');

        $priceHistories = SecurityPriceHistory::whereIn(
            'security_id',
            $securityIds
        )

            ->where('price_date', '>=', $startDate)

            ->orderBy('price_date')

            ->get();

        $groupedByDate = [];

        foreach ($priceHistories as $history) {

            $symbol = $securities

                ->get($history->security_id)

                ?->symbol;

            if (! $symbol) {
                continue;
            }

            $date = $history->price_date->toDateString();

            if (! isset($groupedByDate[$date])) {

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

<?php

namespace App\Queries\Comparisons;

use App\Models\Etf;
use App\Models\EtfNavHistory;

class SymbolNavHistoryChartQuery
{
    public function getData(
        array $etfIds,
        string $startDate
    ): array {

        $etfs = Etf::whereIn('id', $etfIds)

            ->get()

            ->keyBy('id');

        $navHistories = EtfNavHistory::whereIn(
            'etf_id',
            $etfIds
        )

            ->where('nav_date', '>=', $startDate)

            ->orderBy('nav_date')

            ->get();

        $groupedByDate = [];

        foreach ($navHistories as $history) {

            $symbol = $etfs

                ->get($history->etf_id)

                ?->symbol;

            if (!$symbol) {
                continue;
            }

            $date = $history

                ->nav_date

                ->toDateString();

            if (!isset($groupedByDate[$date])) {

                $groupedByDate[$date] = [

                    'date' => $date,

                ];
            }

            $groupedByDate[$date][$symbol] =
                (float) $history->nav_per_share;
        }

        return array_values($groupedByDate);
    }
}

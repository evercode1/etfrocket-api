<?php

namespace App\Queries\Comparisons;

use App\Models\Etf;
use App\Models\EtfAumHistory;

class SymbolAumHistoryChartQuery
{
    public function getData(
        array $etfIds,
        string $startDate
    ): array {

        $etfs = Etf::whereIn('id', $etfIds)

            ->get()

            ->keyBy('id');

        $aumHistories = EtfAumHistory::whereIn(
            'etf_id',
            $etfIds
        )

            ->where('aum_date', '>=', $startDate)

            ->orderBy('aum_date')

            ->get();

        $groupedByDate = [];

        foreach ($aumHistories as $history) {

            $symbol = $etfs

                ->get($history->etf_id)

                ?->symbol;

            if (! $symbol) {
                continue;
            }

            $date = $history

                ->aum_date

                ->toDateString();

            if (! isset($groupedByDate[$date])) {

                $groupedByDate[$date] = [

                    'date' => $date,

                ];
            }

            $groupedByDate[$date][$symbol] =
                (float) $history->assets_under_management;
        }

        return array_values($groupedByDate);
    }
}

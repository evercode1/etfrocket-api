<?php

namespace App\Queries\Comparisons;

use App\Models\Etf;
use App\Models\EtfDividendHistory;

class SymbolDividendHistoryChartQuery
{
    public function getData(
        array $etfIds,
        string $startDate
    ): array {

        $etfs = Etf::whereIn('id', $etfIds)

            ->get()

            ->keyBy('id');

        $dividendHistories = EtfDividendHistory::whereIn(
            'etf_id',
            $etfIds
        )

            ->where('ex_dividend_date', '>=', $startDate)

            ->orderBy('ex_dividend_date')

            ->get();

        $groupedByDate = [];

        foreach ($dividendHistories as $history) {

            $symbol = $etfs

                ->get($history->etf_id)

                ?->symbol;

            if (!$symbol) {
                continue;
            }

            $date = $history

                ->ex_dividend_date

                ->toDateString();

            if (!isset($groupedByDate[$date])) {

                $groupedByDate[$date] = [

                    'date' => $date,

                ];
            }

            $groupedByDate[$date][$symbol] =
                (float) $history->dividend_amount;
        }

        return array_values($groupedByDate);
    }
}

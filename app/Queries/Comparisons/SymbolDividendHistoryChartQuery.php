<?php

namespace App\Queries\Comparisons;

use App\Models\Security;
use App\Models\SecurityDividendHistory;

class SymbolDividendHistoryChartQuery
{
    public function getData(
        array $securityIds,
        string $startDate
    ): array {

        $securities = Security::whereIn('id', $securityIds)

            ->get()

            ->keyBy('id');

        $dividendHistories = SecurityDividendHistory::whereIn(
            'security_id',
            $securityIds
        )

            ->where('ex_dividend_date', '>=', $startDate)

            ->orderBy('ex_dividend_date')

            ->get();

        $groupedByDate = [];

        foreach ($dividendHistories as $history) {

            $symbol = $securities

                ->get($history->security_id)

                ?->symbol;

            if (! $symbol) {
                continue;
            }

            $date = $history

                ->ex_dividend_date

                ->toDateString();

            if (! isset($groupedByDate[$date])) {

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

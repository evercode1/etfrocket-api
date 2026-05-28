<?php

namespace App\Queries\Comparisons;

use App\Models\EtfNavHistory;
use App\Models\Security;

class SymbolNavHistoryChartQuery
{
    public function getData(
        array $securityIds,
        string $startDate
    ): array {

        $securities = Security::whereIn('id', $securityIds)

            ->get()

            ->keyBy('id');

        $navHistories = EtfNavHistory::whereIn(
            'security_id',
            $securityIds
        )

            ->where('nav_date', '>=', $startDate)

            ->orderBy('nav_date')

            ->get();

        $groupedByDate = [];

        foreach ($navHistories as $history) {

            $symbol = $securities

                ->get($history->security_id)

                ?->symbol;

            if (! $symbol) {
                continue;
            }

            $date = $history

                ->nav_date

                ->toDateString();

            if (! isset($groupedByDate[$date])) {

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

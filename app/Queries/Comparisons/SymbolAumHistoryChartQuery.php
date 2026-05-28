<?php

namespace App\Queries\Comparisons;

use App\Models\Security;
use App\Models\SecurityAumHistory;

class SymbolAumHistoryChartQuery
{
    public function getData(
        array $securityIds,
        string $startDate
    ): array {

        $securities = Security::whereIn('id', $securityIds)

            ->get()

            ->keyBy('id');

        $aumHistories = SecurityAumHistory::whereIn(
            'security_id',
            $securityIds
        )

            ->where('aum_date', '>=', $startDate)

            ->orderBy('aum_date')

            ->get();

        $groupedByDate = [];

        foreach ($aumHistories as $history) {

            $symbol = $securities

                ->get($history->security_id)

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

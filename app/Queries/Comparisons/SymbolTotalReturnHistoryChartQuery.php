<?php

namespace App\Queries\Comparisons;

use App\Models\Security;
use App\Models\SecurityDividendHistory;
use App\Models\SecurityPriceHistory;
use Illuminate\Support\Collection;

class SymbolTotalReturnHistoryChartQuery
{
    public function getData(
        array $securityIds,
        string $startDate
    ): array {

        if (empty($securityIds)) {
            return [];
        }

        $securities = Security::query()

            ->whereIn('id', $securityIds)

            ->get([
                'id',
                'symbol',
            ])

            ->keyBy('id');

        $pricesBySecurity = SecurityPriceHistory::query()

            ->whereIn('security_id', $securityIds)

            ->whereDate('price_date', '>=', $startDate)

            ->whereNotNull('close_price')

            ->orderBy('security_id')

            ->orderBy('price_date')

            ->get([
                'security_id',
                'price_date',
                'close_price',
            ])

            ->groupBy('security_id');

        $dividendsBySecurity = SecurityDividendHistory::query()

            ->whereIn('security_id', $securityIds)

            ->whereDate('ex_dividend_date', '>=', $startDate)

            ->whereNotNull('dividend_amount')

            ->orderBy('security_id')

            ->orderBy('ex_dividend_date')

            ->get([
                'security_id',
                'ex_dividend_date',
                'dividend_amount',
            ])

            ->groupBy('security_id');

        $rowsByDate = [];

        foreach ($securityIds as $securityId) {

            $security = $securities->get($securityId);

            /** @var Collection<int, SecurityPriceHistory> $prices */
            $prices = $pricesBySecurity->get(
                $securityId,
                collect()
            );

            if (! $security || $prices->isEmpty()) {
                continue;
            }

            $startPrice = (float) $prices
                ->first()
                ->close_price;

            if ($startPrice <= 0) {
                continue;
            }

            $dividendsByDate = $dividendsBySecurity

                ->get(
                    $securityId,
                    collect()
                )

                ->groupBy(
                    fn ($dividend) => $dividend
                        ->ex_dividend_date
                        ->toDateString()
                )

                ->map(
                    fn (Collection $dividends) => $dividends->sum(
                        fn ($dividend) => (float) $dividend
                            ->dividend_amount
                    )
                );

            $cumulativeDividends = 0.0;

            foreach ($prices as $price) {

                $date = $price
                    ->price_date
                    ->toDateString();

                $cumulativeDividends += (float) $dividendsByDate
                    ->get(
                        $date,
                        0
                    );

                $closePrice = (float) $price->close_price;

                $totalReturnPercentage = (
                    (
                        $closePrice
                        - $startPrice
                        + $cumulativeDividends
                    )
                    / $startPrice
                ) * 100;

                if (! isset($rowsByDate[$date])) {
                    $rowsByDate[$date] = [
                        'date' => $date,
                    ];
                }

                $rowsByDate[$date][$security->symbol] = round(
                    $totalReturnPercentage,
                    4
                );
            }
        }

        ksort($rowsByDate);

        return array_values($rowsByDate);
    }
}

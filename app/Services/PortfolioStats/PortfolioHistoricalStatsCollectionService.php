<?php

namespace App\Services\PortfolioStats;

use Illuminate\Support\Collection;

class PortfolioHistoricalStatsCollectionService
{
    private const BUY = 1;

    public function getHoldingsAsOfDate(
        Collection $transactions,
        string $asOfDate
    ): Collection {

        $holdings = [];

        foreach (

            $transactions

                ->filter(

                    fn ($transaction) => $transaction->transaction_date->toDateString() <= $asOfDate

                ) as $transaction

        ) {

            $shares =

                $transaction->transaction_type_id == self::BUY

                ? $transaction->shares

                : -$transaction->shares;

            $holdings[$transaction->security_id] =

                ($holdings[$transaction->security_id] ?? 0)

                + $shares;
        }

        return collect($holdings)

            ->filter(

                fn ($shares) => $shares > 0

            )

            ->map(

                fn ($shares, $securityId) => [

                    'security_id' => (int) $securityId,

                    'shares' => round(

                        (float) $shares,

                        4

                    ),

                ]

            )

            ->values();
    }

    public function getSharesOwnedAsOfDate(
        Collection $transactions,
        int $securityId,
        string $asOfDate
    ): float {

        $shares = 0;

        foreach (

            $transactions

                ->filter(

                    fn ($transaction) => $transaction->security_id == $securityId

                    &&

                    $transaction->transaction_date->toDateString() <= $asOfDate

                ) as $transaction

        ) {

            $shares +=

                $transaction->transaction_type_id == self::BUY

                ? $transaction->shares

                : -$transaction->shares;
        }

        return round(

            $shares,

            4

        );
    }

    public function getPortfolioValueAsOfDate(
        Collection $transactions,
        Collection $pricesBySecurity,
        string $asOfDate
    ): float {

        $holdings =

            $this->getHoldingsAsOfDate(

                $transactions,

                $asOfDate

            );

        $value = 0;

        foreach ($holdings as $holding) {

            $securityPrices =

                $pricesBySecurity->get(

                    $holding['security_id'],

                    collect()

                );

            $latestPrice =

                $securityPrices

                    ->filter(

                        fn ($price) => $price->price_date->toDateString() <= $asOfDate

                    )

                    ->last();

            if (! $latestPrice) {

                continue;
            }

            $value +=

                $holding['shares']

                *

                (float) $latestPrice->close_price;
        }

        return round(

            $value,

            4

        );
    }

    public function getDividendIncomeBetweenDates(
        Collection $transactions,
        Collection $dividendsBySecurity,
        string $startDate,
        string $endDate
    ): float {

        $income = 0;

        foreach (

            $dividendsBySecurity as $securityId => $securityDividends

        ) {

            foreach (

                $securityDividends

                    ->filter(

                        fn ($dividend) => $dividend->ex_dividend_date->toDateString() >= $startDate

                        &&

                        $dividend->ex_dividend_date->toDateString() <= $endDate

                    ) as $dividend

            ) {

                $sharesOwned =

                    $this->getSharesOwnedAsOfDate(

                        $transactions,

                        (int) $securityId,

                        $dividend->ex_dividend_date->toDateString()

                    );

                if ($sharesOwned <= 0) {

                    continue;
                }

                $income +=

                    $sharesOwned *

                    (float) $dividend->dividend_amount;
            }
        }

        return round(
            $income,
            4
        );
    }
}

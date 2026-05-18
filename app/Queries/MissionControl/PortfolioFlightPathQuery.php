<?php

namespace App\Queries\MissionControl;

use App\Models\EtfDividendHistory;
use App\Models\EtfPriceHistory;
use App\Models\PortfolioTransaction;
use Carbon\Carbon;

class PortfolioFlightPathQuery
{
    public function getData(int $portfolio_id): array
    {
        $transactions = PortfolioTransaction::where('portfolio_id', $portfolio_id)
            ->where('transaction_type_id', 1)
            ->orderBy('transaction_date')
            ->get();

        if ($transactions->isEmpty()) {
            return [];
        }

        $startDate = Carbon::parse(
            $transactions->min('transaction_date')
        )->startOfMonth();

        $endDate = now()->startOfMonth();

        $points = [];

        $cursor = $startDate->copy();

        while ($cursor->lte($endDate)) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd = $cursor->copy()->endOfMonth();

            if ($monthEnd->gt(now())) {
                $monthEnd = now();
            }

            $points[] = [
                'date' => $monthStart->format('M Y'),
                'value' => $this->calculatePortfolioValue(
                    $portfolio_id,
                    $monthEnd->format('Y-m-d')
                ),
                'income' => $this->calculateMonthlyIncome(
                    $portfolio_id,
                    $monthStart->format('Y-m-d'),
                    $monthEnd->format('Y-m-d')
                ),
            ];

            $cursor->addMonth();
        }

        return $points;
    }

    private function calculatePortfolioValue(
        int $portfolio_id,
        string $asOfDate
    ): float {
        $holdings = PortfolioTransaction::query()
            ->selectRaw('
                etf_id,
                    SUM(
                        CASE
                        WHEN transaction_type_id = 1 THEN shares
                        WHEN transaction_type_id = 2 THEN -shares
                        ELSE 0
                    END
                    ) as shares
        ')
            ->where('portfolio_id', $portfolio_id)
            ->where('transaction_date', '<=', $asOfDate)
            ->groupBy('etf_id')
            ->get();

        $value = 0;

        foreach ($holdings as $holding) {
            $price = EtfPriceHistory::where('etf_id', $holding->etf_id)
                ->where('price_date', '<=', $asOfDate)
                ->orderByDesc('price_date')
                ->value('close_price');

            if (! $price) {
                continue;
            }

            $value += (float) $holding->shares * (float) $price;
        }

        return round($value, 4);
    }

    private function calculateMonthlyIncome(
        int $portfolio_id,
        string $monthStart,
        string $monthEnd
    ): float {
        $dividends = EtfDividendHistory::whereBetween('ex_dividend_date', [
            $monthStart,
            $monthEnd,
        ])
            ->get();

        $income = 0;

        foreach ($dividends as $dividend) {
            $sharesOwned = PortfolioTransaction::query()
                ->where('portfolio_id', $portfolio_id)
                ->where('etf_id', $dividend->etf_id)
                ->where('transaction_date', '<=', $dividend->ex_dividend_date)
                ->selectRaw('
                    SUM(
                        CASE
                        WHEN transaction_type_id = 1 THEN shares
                        WHEN transaction_type_id = 2 THEN -shares
                        ELSE 0
                    END
            ) as shares
    ')
                ->value('shares');

            if ($sharesOwned <= 0) {
                continue;
            }

            $income += (float) $sharesOwned * (float) $dividend->dividend_amount;
        }

        return round($income, 4);
    }
}

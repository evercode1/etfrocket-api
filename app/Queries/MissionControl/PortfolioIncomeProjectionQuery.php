<?php

namespace App\Queries\MissionControl;

use App\Models\PortfolioTransaction;
use App\Models\SecurityDividendHistory;

class PortfolioIncomeProjectionQuery
{
    public function getData(int $portfolio_id, int $months = 6): array
    {
        $holdings = PortfolioTransaction::query()

            ->selectRaw('
                portfolio_transactions.security_id,
                securities.distribution_frequency_id,
                SUM(
                    CASE
                        WHEN portfolio_transactions.transaction_type_id = 1 THEN portfolio_transactions.shares
                        WHEN portfolio_transactions.transaction_type_id = 2 THEN -portfolio_transactions.shares
                        ELSE 0
                    END
                ) as shares
            ')

            ->join('securities', 'portfolio_transactions.security_id', '=', 'securities.id')

            ->where('portfolio_transactions.portfolio_id', $portfolio_id)

            ->groupBy([
                'portfolio_transactions.security_id',
                'securities.distribution_frequency_id',
            ])

            ->having('shares', '>', 0)

            ->get();

        if ($holdings->isEmpty()) {
            return [];
        }

        $monthlyIncome = 0;

        foreach ($holdings as $holding) {

            $recentDividends = SecurityDividendHistory::where('security_id', $holding->security_id)

                ->orderByDesc('ex_dividend_date')

                ->limit(4)

                ->pluck('dividend_amount');

            if ($recentDividends->isEmpty()) {
                continue;
            }

            $averageDividend = $recentDividends->avg();

            $monthlyMultiplier = $this->getMonthlyDistributionMultiplier(
                $holding->distribution_frequency_id
            );

            $monthlyIncome += (float) $holding->shares
                * (float) $averageDividend
                * $monthlyMultiplier;
        }

        $results = [];

        $cursor = now()->startOfMonth();

        for ($index = 0; $index < $months; $index++) {
            $month = $cursor->copy()->addMonths($index);

            $results[] = [
                'month' => $month->format('M'),
                'income' => round($monthlyIncome, 4),
            ];
        }

        return $results;
    }

    private function getMonthlyDistributionMultiplier(?int $distributionFrequencyId): float
    {
        return match ((int) $distributionFrequencyId) {
            1 => 30.0,
            2 => 52 / 12,
            3 => 26 / 12,
            4 => 1.0,
            5 => 1 / 3,
            6 => 1 / 6,
            7 => 1 / 12,
            8 => 1.0,
            9 => 0.0,
            default => 1.0,
        };
    }
}

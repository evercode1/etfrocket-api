<?php

namespace App\Queries\Dividends;

use App\Models\EtfDividendHistory;
use App\Models\PortfolioTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DividendIncomeTimelineQuery
{
    private const BUY = 1;
    private const SELL = 2;

    public function getData(int $portfolioId): array
    {
        $holdings = $this->getHoldings($portfolioId);

        if ($holdings->isEmpty()) {
            return [];
        }

        $startMonth = Carbon::now()->startOfMonth();

        return collect(range(0, 4))
            ->map(function (int $monthOffset) use ($holdings, $startMonth) {

                $month = $startMonth->copy()->addMonths($monthOffset);

                return [
                    'month' => $month->format('M'),
                    'income' => round(
                        $this->getProjectedIncomeForMonth($holdings, $month),
                        2
                    ),
                ];
            })
            ->toArray();
    }

    private function getHoldings(int $portfolioId): Collection
    {
        return PortfolioTransaction::query()
            ->select([
                'portfolio_transactions.etf_id',
                'etfs.symbol',
                'etfs.distribution_frequency_id',
                DB::raw('
                    SUM(
                        CASE
                            WHEN portfolio_transactions.transaction_type_id = ' . self::BUY . ' THEN portfolio_transactions.shares
                            WHEN portfolio_transactions.transaction_type_id = ' . self::SELL . ' THEN -portfolio_transactions.shares
                            ELSE 0
                        END
                    ) as shares
                '),
            ])
            ->join('etfs', 'portfolio_transactions.etf_id', '=', 'etfs.id')
            ->where('portfolio_transactions.portfolio_id', $portfolioId)
            ->groupBy([
                'portfolio_transactions.etf_id',
                'etfs.symbol',
                'etfs.distribution_frequency_id',
            ])
            ->having('shares', '>', 0)
            ->get();
    }

    private function getProjectedIncomeForMonth(Collection $holdings, Carbon $month): float
    {
        $income = 0;

        foreach ($holdings as $holding) {
            $averageDividend = $this->getAverageRecentDividend(
                (int) $holding->etf_id
            );

            $monthlyMultiplier = $this->getMonthlyDistributionMultiplier(
                (int) $holding->distribution_frequency_id
            );

            $income += (float) $holding->shares * $averageDividend * $monthlyMultiplier;
        }

        return $income;
    }

    private function getAverageRecentDividend(int $etfId): float
    {
        $recentDividends = EtfDividendHistory::where('etf_id', $etfId)
            ->orderByDesc('ex_dividend_date')
            ->limit(4)
            ->pluck('dividend_amount');

        if ($recentDividends->isEmpty()) {
            return 0;
        }

        return (float) $recentDividends->avg();
    }

    private function getMonthlyDistributionMultiplier(int $distributionFrequencyId): float
    {
        return match ($distributionFrequencyId) {
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

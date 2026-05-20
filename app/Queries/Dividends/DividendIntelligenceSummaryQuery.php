<?php

namespace App\Queries\Dividends;

use App\Models\EtfDividendHistory;
use App\Models\PortfolioTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DividendIntelligenceSummaryQuery
{
    private const BUY = 1;
    private const SELL = 2;
    private const WEEKLY = 2;
    private const RECENT_MONTHS_TO_AVERAGE = 3;

    public function getData(int $portfolioId): array
    {
        $holdings = $this->getHoldings($portfolioId);

        if ($holdings->isEmpty()) {
            return [
                'has_holdings' => false,
                'projected_monthly_income' => 0,
                'upcoming_weekly_events_count' => 0,
                'forward_yield_percentage' => null,
                'dividend_growth_percentage' => null,
            ];
        }

        $costBasis = 0;
        $upcomingWeeklyEventsCount = 0;

        foreach ($holdings as $holding) {
            $costBasis += (float) $holding->cost_basis;

            if ((int) $holding->distribution_frequency_id === self::WEEKLY) {
                $upcomingWeeklyEventsCount++;
            }
        }

        $projectedMonthlyIncome = $this->getAverageRecentMonthlyIncome(
            $holdings
        );

        $forwardYieldPercentage = $costBasis > 0
            ? (($projectedMonthlyIncome * 12) / $costBasis) * 100
            : null;

        return [
            'has_holdings' => true,
            'projected_monthly_income' => round($projectedMonthlyIncome, 4),
            'upcoming_weekly_events_count' => $upcomingWeeklyEventsCount,
            'forward_yield_percentage' => is_null($forwardYieldPercentage)
                ? null
                : round($forwardYieldPercentage, 4),
            'dividend_growth_percentage' => $this->getDividendGrowthPercentage($holdings),
        ];
    }

    private function getHoldings(int $portfolioId): Collection
    {
        return PortfolioTransaction::query()
            ->select([
                'portfolio_transactions.etf_id',
                'etfs.symbol',
                'etfs.fund_name',
                'etfs.distribution_frequency_id',
                'distribution_frequencies.distribution_frequency_name',
                DB::raw('
                    SUM(
                        CASE
                            WHEN portfolio_transactions.transaction_type_id = ' . self::BUY . ' THEN portfolio_transactions.shares
                            WHEN portfolio_transactions.transaction_type_id = ' . self::SELL . ' THEN -portfolio_transactions.shares
                            ELSE 0
                        END
                    ) as shares
                '),
                DB::raw('
                    SUM(
                        CASE
                            WHEN portfolio_transactions.transaction_type_id = ' . self::BUY . ' THEN portfolio_transactions.shares * portfolio_transactions.price_per_share
                            WHEN portfolio_transactions.transaction_type_id = ' . self::SELL . ' THEN -portfolio_transactions.shares * portfolio_transactions.price_per_share
                            ELSE 0
                        END
                    ) as cost_basis
                '),
            ])
            ->join('etfs', 'portfolio_transactions.etf_id', '=', 'etfs.id')
            ->leftJoin(
                'distribution_frequencies',
                'etfs.distribution_frequency_id',
                '=',
                'distribution_frequencies.id'
            )
            ->where('portfolio_transactions.portfolio_id', $portfolioId)
            ->groupBy([
                'portfolio_transactions.etf_id',
                'etfs.symbol',
                'etfs.fund_name',
                'etfs.distribution_frequency_id',
                'distribution_frequencies.distribution_frequency_name',
            ])
            ->having('shares', '>', 0)
            ->get();
    }

    private function getAverageRecentMonthlyIncome(Collection $holdings): float
    {
        $etfIds = $holdings->pluck('etf_id')->toArray();

        $latestDividendDate = EtfDividendHistory::whereIn('etf_id', $etfIds)
            ->max('ex_dividend_date');

        if (! $latestDividendDate) {
            return 0;
        }

        $latestMonth = Carbon::parse($latestDividendDate)->startOfMonth();

        $monthlyIncomeRows = collect(range(0, self::RECENT_MONTHS_TO_AVERAGE - 1))
            ->map(function (int $monthOffset) use ($holdings, $latestMonth) {
                $month = $latestMonth->copy()->subMonths($monthOffset);

                return $this->getMonthlyDividendIncome($holdings, $month);
            })
            ->filter(fn(float $income) => $income > 0)
            ->values();

        if ($monthlyIncomeRows->isEmpty()) {
            return 0;
        }

        return (float) $monthlyIncomeRows->avg();
    }

    private function getDividendGrowthPercentage(Collection $holdings): ?float
    {
        $etfIds = $holdings->pluck('etf_id')->toArray();

        $latestDividendDate = EtfDividendHistory::whereIn('etf_id', $etfIds)
            ->max('ex_dividend_date');

        if (! $latestDividendDate) {
            return null;
        }

        $latestMonth = Carbon::parse($latestDividendDate)->startOfMonth();
        $previousMonth = $latestMonth->copy()->subMonth();

        $latestMonthIncome = $this->getMonthlyDividendIncome(
            $holdings,
            $latestMonth
        );

        $previousMonthIncome = $this->getMonthlyDividendIncome(
            $holdings,
            $previousMonth
        );

        if ($previousMonthIncome <= 0) {
            return null;
        }

        return round(
            (($latestMonthIncome - $previousMonthIncome) / $previousMonthIncome) * 100,
            4
        );
    }

    private function getMonthlyDividendIncome(Collection $holdings, Carbon $month): float
    {
        $monthStart = $month->copy()->startOfMonth()->toDateString();
        $monthEnd = $month->copy()->endOfMonth()->toDateString();

        $income = 0;

        foreach ($holdings as $holding) {
            $dividendTotal = EtfDividendHistory::where('etf_id', $holding->etf_id)
                ->whereBetween('ex_dividend_date', [$monthStart, $monthEnd])
                ->sum('dividend_amount');

            $income += (float) $holding->shares * (float) $dividendTotal;
        }

        return $income;
    }
}

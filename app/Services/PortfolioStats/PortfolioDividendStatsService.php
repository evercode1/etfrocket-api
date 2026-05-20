<?php

namespace App\Services\PortfolioStats;

use App\Models\EtfDividendHistory;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PortfolioDividendStatsService
{
    public function getMonthlyDividendIncome(Collection $holdings, Carbon $month): float
    {
        $monthStart = $month->copy()->startOfMonth()->toDateString();
        $monthEnd = $month->copy()->endOfMonth()->toDateString();

        $income = 0;

        foreach ($holdings as $holding) {
            $dividendTotal = EtfDividendHistory::where('etf_id', $holding['etf_id'])
                ->whereBetween('ex_dividend_date', [$monthStart, $monthEnd])
                ->sum('dividend_amount');

            $income += (float) $holding['shares'] * (float) $dividendTotal;
        }

        return round($income, 4);
    }

    public function getAverageRecentMonthlyIncome(
        Collection $holdings,
        int $months = 3
    ): float {
        if ($holdings->isEmpty()) {
            return 0;
        }

        $latestDividendDate = $this->getLatestDividendDate($holdings);

        if (! $latestDividendDate) {
            return 0;
        }

        $latestMonth = Carbon::parse($latestDividendDate)->startOfMonth();

        $monthlyIncomeRows = collect(range(0, $months - 1))
            ->map(function (int $monthOffset) use ($holdings, $latestMonth) {
                $month = $latestMonth->copy()->subMonths($monthOffset);

                return $this->getMonthlyDividendIncome($holdings, $month);
            })
            ->filter(fn(float $income) => $income > 0)
            ->values();

        if ($monthlyIncomeRows->isEmpty()) {
            return 0;
        }

        return round((float) $monthlyIncomeRows->avg(), 4);
    }

    public function getProjectedMonthlyIncome(Collection $holdings): float
    {
        return $this->getAverageRecentMonthlyIncome($holdings, 3);
    }

    public function getDividendGrowthPercentage(Collection $holdings): ?float
    {
        if ($holdings->isEmpty()) {
            return null;
        }

        $latestCompleteMonth = Carbon::now()
            ->subMonth()
            ->startOfMonth();

        $previousCompleteMonth = $latestCompleteMonth
            ->copy()
            ->subMonth();

        $latestCompleteMonthIncome = $this->getMonthlyDividendIncome(
            $holdings,
            $latestCompleteMonth
        );

        $previousCompleteMonthIncome = $this->getMonthlyDividendIncome(
            $holdings,
            $previousCompleteMonth
        );

        if ($previousCompleteMonthIncome <= 0) {
            return null;
        }

        if ($latestCompleteMonthIncome <= 0) {
            return null;
        }

        return round(
            (($latestCompleteMonthIncome - $previousCompleteMonthIncome) / $previousCompleteMonthIncome) * 100,
            4
        );
    }

    public function getForwardYieldPercentage(
        Collection $holdings,
        float $projectedMonthlyIncome
    ): ?float {
        $costBasis = $holdings->sum('cost_basis');

        if ($costBasis <= 0) {
            return null;
        }

        return round((($projectedMonthlyIncome * 12) / $costBasis) * 100, 4);
    }

    public function getLatestDividendDate(Collection $holdings): ?string
    {
        $etfIds = $holdings->pluck('etf_id')->toArray();

        if (empty($etfIds)) {
            return null;
        }

        return EtfDividendHistory::whereIn('etf_id', $etfIds)
            ->max('ex_dividend_date');
    }

    public function getProjectedIncomeTimeline(
        Collection $holdings,
        int $months = 5,
        float $annualGrowthRate = 0.08
    ): array {
        $projectedMonthlyIncome = $this->getProjectedMonthlyIncome($holdings);

        $monthlyGrowthRate = $annualGrowthRate / 12;

        $currentMonth = Carbon::now()->startOfMonth();

        return collect(range(0, $months - 1))
            ->map(function (int $monthOffset) use (
                $projectedMonthlyIncome,
                $monthlyGrowthRate,
                $currentMonth
            ) {
                $month = $currentMonth->copy()->addMonths($monthOffset);

                $growthMultiplier = pow(
                    1 + $monthlyGrowthRate,
                    $monthOffset
                );

                $projectedIncome = $projectedMonthlyIncome * $growthMultiplier;

                return [
                    'month' => $month->format('M'),
                    'income' => round($projectedIncome, 2),
                ];
            })
            ->toArray();
    }
}

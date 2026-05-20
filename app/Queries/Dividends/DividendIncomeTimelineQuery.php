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
    private const RECENT_MONTHS_TO_AVERAGE = 3;
    private const FUTURE_MONTHS_TO_PROJECT = 5;

    public function getData(int $portfolioId): array
    {
        $holdings = $this->getHoldings($portfolioId);

        if ($holdings->isEmpty()) {
            return [];
        }

        $projectedMonthlyIncome = $this->getAverageRecentMonthlyIncome(
            $holdings
        );

        $startMonth = Carbon::now()->startOfMonth();

        return collect(range(0, self::FUTURE_MONTHS_TO_PROJECT - 1))
            ->map(function (int $monthOffset) use ($projectedMonthlyIncome, $startMonth) {
                $month = $startMonth->copy()->addMonths($monthOffset);

                return [
                    'month' => $month->format('M'),
                    'income' => round($projectedMonthlyIncome, 2),
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

<?php

namespace App\Queries\Dividends;

use App\Models\EtfDividendHistory;
use App\Models\PortfolioTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DividendSignalsQuery
{
    private const BUY = 1;
    private const SELL = 2;
    private const WEEKLY = 2;

    public function getData(int $portfolioId): array
    {
        $holdings = $this->getHoldings($portfolioId);

        if ($holdings->isEmpty()) {
            return [];
        }

        return [
            $this->getDistributionGrowthSignal($holdings),
            $this->getWeeklyCadenceSignal($holdings),
            $this->getIncomeStabilitySignal($holdings),
        ];
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

    private function getDistributionGrowthSignal(Collection $holdings): array
    {
        $growthRows = [];

        foreach ($holdings as $holding) {
            $latestTwoDividends = EtfDividendHistory::where('etf_id', $holding->etf_id)
                ->orderByDesc('ex_dividend_date')
                ->limit(2)
                ->get();

            if ($latestTwoDividends->count() < 2) {
                continue;
            }

            $latest = (float) $latestTwoDividends[0]->dividend_amount;
            $previous = (float) $latestTwoDividends[1]->dividend_amount;

            if ($previous <= 0) {
                continue;
            }

            $growthPercentage = (($latest - $previous) / $previous) * 100;

            if ($growthPercentage > 0) {
                $growthRows[] = [
                    'symbol' => $holding->symbol,
                    'growth_percentage' => round($growthPercentage, 2),
                ];
            }
        }

        $affectedEtfs = collect($growthRows)
            ->sortByDesc('growth_percentage')
            ->pluck('symbol')
            ->values()
            ->toArray();

        if (empty($affectedEtfs)) {
            return [
                'title' => 'Distribution Growth',
                'message' => 'No recent distribution growth was detected across current holdings.',
                'affected_etfs' => [],
                'observation' => 'Recent distributions are flat or lower compared to the prior payout.',
                'possible_causes' => [
                    'Options premium may be lower',
                    'Underlying volatility may have declined',
                    'Recent payout cycle may be normalizing',
                ],
            ];
        }

        $topGrowth = collect($growthRows)->max('growth_percentage');

        return [
            'title' => 'Distribution Growth',
            'message' => implode(', ', array_slice($affectedEtfs, 0, 3)) . ' showed recent distribution growth.',
            'affected_etfs' => $affectedEtfs,
            'observation' => 'The strongest recent distribution increase was ' . round($topGrowth, 2) . '% compared to the prior payout.',
            'possible_causes' => [
                'Higher options premium',
                'Increased implied volatility',
                'Improved underlying price action',
            ],
        ];
    }

    private function getWeeklyCadenceSignal(Collection $holdings): array
    {
        $weeklyHoldings = $holdings->filter(function ($holding) {
            return (int) $holding->distribution_frequency_id === self::WEEKLY;
        });

        if ($weeklyHoldings->isEmpty()) {
            return [
                'title' => 'Weekly Cadence Watch',
                'message' => 'No weekly dividend holdings were detected in this portfolio.',
                'affected_etfs' => [],
                'observation' => 'Weekly dividend cadence tracking is only available for weekly distribution ETFs.',
                'possible_causes' => [
                    'Portfolio may contain monthly or variable payers',
                    'Weekly distribution ETFs may not be held',
                    'Distribution frequency data may be unavailable',
                ],
            ];
        }

        $today = Carbon::today();

        $expectedEtfs = [];

        foreach ($weeklyHoldings as $holding) {
            $futureDeclaredDividend = EtfDividendHistory::where('etf_id', $holding->etf_id)
                ->whereDate('ex_dividend_date', '>=', $today->toDateString())
                ->exists();

            if ($futureDeclaredDividend) {
                continue;
            }

            $latestDividend = EtfDividendHistory::where('etf_id', $holding->etf_id)
                ->orderByDesc('ex_dividend_date')
                ->first();

            if ($latestDividend) {
                $expectedEtfs[] = $holding->symbol;
            }
        }

        if (empty($expectedEtfs)) {
            return [
                'title' => 'Weekly Cadence Watch',
                'message' => 'Weekly dividend holdings have declared upcoming dividend events or lack enough history for cadence estimates.',
                'affected_etfs' => $weeklyHoldings->pluck('symbol')->values()->toArray(),
                'observation' => 'No undeclared weekly events were detected from cadence logic.',
                'possible_causes' => [
                    'Upcoming dividends may already be declared',
                    'New holdings may not have dividend history yet',
                    'Weekly cadence may be current',
                ],
            ];
        }

        return [
            'title' => 'Weekly Cadence Watch',
            'message' => 'Some weekly payer events are expected but not yet declared. Amounts remain TBD until confirmed.',
            'affected_etfs' => $expectedEtfs,
            'observation' => 'Upcoming weekly dividend events are expected based on payout cadence, but official declarations have not been posted yet.',
            'possible_causes' => [
                'Weekly declaration not yet released',
                'Holiday-adjusted payout schedule',
                'Provider timing delay',
            ],
        ];
    }

    private function getIncomeStabilitySignal(Collection $holdings): array
    {
        $monthlyIncomeRows = $this->getRecentMonthlyIncomeRows($holdings);

        if (count($monthlyIncomeRows) < 2) {
            return [
                'title' => 'Income Stability',
                'message' => 'More dividend history is needed to evaluate portfolio income stability.',
                'affected_etfs' => ['Portfolio'],
                'observation' => 'At least two months of dividend data are needed to compare income consistency.',
                'possible_causes' => [
                    'Portfolio may be new',
                    'Dividend history may be incomplete',
                    'Recent holdings may not have paid yet',
                ],
            ];
        }

        $averageIncome = collect($monthlyIncomeRows)->avg('income');

        if ($averageIncome <= 0) {
            return [
                'title' => 'Income Stability',
                'message' => 'Dividend income stability cannot be evaluated because recent income is zero.',
                'affected_etfs' => ['Portfolio'],
                'observation' => 'No recent paid dividend income was detected across the current holdings.',
                'possible_causes' => [
                    'No dividends paid in the recent window',
                    'Dividend histories may be missing',
                    'Current holdings may not have distributed yet',
                ],
            ];
        }

        $maxIncome = collect($monthlyIncomeRows)->max('income');
        $minIncome = collect($monthlyIncomeRows)->min('income');

        $spreadPercentage = (($maxIncome - $minIncome) / $averageIncome) * 100;

        if ($spreadPercentage <= 25) {
            return [
                'title' => 'Income Stability',
                'message' => 'Portfolio income variance remains within healthy expected ranges.',
                'affected_etfs' => ['Portfolio'],
                'observation' => 'Recent monthly income spread is ' . round($spreadPercentage, 2) . '% across the evaluated window.',
                'possible_causes' => [
                    'Diversified ETF income sources',
                    'Consistent weekly payer cadence',
                    'No major distribution cuts detected',
                ],
            ];
        }

        return [
            'title' => 'Income Stability',
            'message' => 'Portfolio income has shown noticeable variation across recent dividend cycles.',
            'affected_etfs' => ['Portfolio'],
            'observation' => 'Recent monthly income spread is ' . round($spreadPercentage, 2) . '% across the evaluated window.',
            'possible_causes' => [
                'Large distribution changes',
                'Irregular payout timing',
                'Concentrated income exposure',
            ],
        ];
    }

    private function getRecentMonthlyIncomeRows(Collection $holdings): array
    {
        $currentMonth = Carbon::today()->startOfMonth();

        return collect(range(0, 2))
            ->map(function (int $monthOffset) use ($holdings, $currentMonth) {
                $month = $currentMonth->copy()->subMonths($monthOffset);

                return [
                    'month' => $month->format('Y-m'),
                    'income' => $this->getMonthlyDividendIncome($holdings, $month),
                ];
            })
            ->filter(function (array $row) {
                return $row['income'] > 0;
            })
            ->values()
            ->toArray();
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

<?php

namespace App\Queries\Dividends;

use App\Models\SecurityDividendHistory;
use App\Services\PortfolioStats\PortfolioDividendStatsService;
use App\Services\PortfolioStats\PortfolioHoldingsStatsService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DividendSignalsQuery
{
    private const WEEKLY = 2;

    public function __construct(
        private PortfolioHoldingsStatsService $holdingsStatsService,
        private PortfolioDividendStatsService $dividendStatsService
    ) {}

    public function getData(int $portfolioId): array
    {
        $holdings = $this->holdingsStatsService->getCurrentHoldings($portfolioId);

        if ($holdings->isEmpty()) {
            return [];
        }

        return [
            $this->getDistributionGrowthSignal($holdings),
            $this->getWeeklyCadenceSignal($holdings),
            $this->getIncomeStabilitySignal($holdings),
        ];
    }

    private function getDistributionGrowthSignal(Collection $holdings): array
    {
        $growthRows = [];

        foreach ($holdings as $holding) {
            $latestTwoDividends = SecurityDividendHistory::where('security_id', $holding['security_id'])
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
                    'symbol' => $holding['symbol'],
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
                'affected_securities' => [],
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
            'message' => implode(', ', array_slice($affectedEtfs, 0, 3)).' showed recent distribution growth.',
            'affected_securities' => $affectedEtfs,
            'observation' => 'The strongest recent distribution increase was '.round($topGrowth, 2).'% compared to the prior payout.',
            'possible_causes' => [
                'Higher options premium',
                'Increased implied volatility',
                'Improved underlying price action',
            ],
        ];
    }

    private function getWeeklyCadenceSignal(Collection $holdings): array
    {
        $weeklyHoldings = $holdings->filter(function (array $holding) {
            return (int) $holding['distribution_frequency_id'] === self::WEEKLY;
        });

        if ($weeklyHoldings->isEmpty()) {
            return [
                'title' => 'Weekly Cadence Watch',
                'message' => 'No weekly dividend holdings were detected in this portfolio.',
                'affected_securities' => [],
                'observation' => 'Weekly dividend cadence tracking is only available for weekly distribution ETFs.',
                'possible_causes' => [
                    'Portfolio may contain monthly or variable payers',
                    'Weekly distribution ETFs may not be held',
                    'Distribution frequency data may be unavailable',
                ],
            ];
        }

        $today = Carbon::today();

        $expectedSecurities = [];

        foreach ($weeklyHoldings as $holding) {
            $futureDeclaredDividend = SecurityDividendHistory::where('security_id', $holding['security_id'])
                ->whereDate('ex_dividend_date', '>=', $today->toDateString())
                ->exists();

            if ($futureDeclaredDividend) {
                continue;
            }

            $latestDividend = SecurityDividendHistory::where('security_id', $holding['security_id'])
                ->orderByDesc('ex_dividend_date')
                ->first();

            if ($latestDividend) {
                $expectedSecurities[] = $holding['symbol'];
            }
        }

        if (empty($expectedSecurities)) {
            return [
                'title' => 'Weekly Cadence Watch',
                'message' => 'Weekly dividend holdings have declared upcoming dividend events or lack enough history for cadence estimates.',
                'affected_securities' => $weeklyHoldings->pluck('symbol')->values()->toArray(),
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
            'affected_securities' => $expectedSecurities,
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
                'affected_securities' => ['Portfolio'],
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
                'affected_securities' => ['Portfolio'],
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
                'affected_securities' => ['Portfolio'],
                'observation' => 'Recent monthly income spread is '.round($spreadPercentage, 2).'% across the evaluated window.',
                'possible_causes' => [
                    'Diversified security income sources',
                    'Consistent weekly payer cadence',
                    'No major distribution cuts detected',
                ],
            ];
        }

        return [
            'title' => 'Income Stability',
            'message' => 'Portfolio income has shown noticeable variation across recent dividend cycles.',
            'affected_securities' => ['Portfolio'],
            'observation' => 'Recent monthly income spread is '.round($spreadPercentage, 2).'% across the evaluated window.',
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
                    'income' => $this->dividendStatsService->getMonthlyDividendIncome(
                        $holdings,
                        $month
                    ),
                ];
            })
            ->filter(function (array $row) {
                return $row['income'] > 0;
            })
            ->values()
            ->toArray();
    }
}

<?php

namespace App\Queries\Dividends;

use App\Services\PortfolioStats\PortfolioDividendStatsService;
use App\Services\PortfolioStats\PortfolioHoldingsStatsService;

class DividendIntelligenceSummaryQuery
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
            return [
                'has_holdings' => false,
                'projected_monthly_income' => 0,
                'upcoming_weekly_events_count' => 0,
                'forward_yield_percentage' => null,
                'dividend_growth_percentage' => null,
            ];
        }

        $projectedMonthlyIncome = $this->dividendStatsService
            ->getProjectedMonthlyIncome($holdings);

        return [
            'has_holdings' => true,

            'projected_monthly_income' => round($projectedMonthlyIncome, 4),

            'upcoming_weekly_events_count' => $holdings
                ->filter(fn(array $holding) => (int) $holding['distribution_frequency_id'] === self::WEEKLY)
                ->count(),

            'forward_yield_percentage' => $this->dividendStatsService
                ->getForwardYieldPercentage($holdings, $projectedMonthlyIncome),

            'dividend_growth_percentage' => $this->dividendStatsService
                ->getDividendGrowthPercentage($holdings),
        ];
    }
}

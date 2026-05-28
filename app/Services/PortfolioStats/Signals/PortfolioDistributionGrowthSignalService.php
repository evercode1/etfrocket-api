<?php

namespace App\Services\PortfolioStats\Signals;

use App\Services\PortfolioStats\PortfolioHoldingsStatsService;
use App\Services\SecurityMetrics\SecurityMetricStatsService;

class PortfolioDistributionGrowthSignalService
{
    public function __construct(
        private PortfolioHoldingsStatsService $holdingsStatsService,
        private SecurityMetricStatsService $securityMetricStatsService
    ) {}

    public function getSignalData(int $portfolioId): array
    {
        $holdings = $this->holdingsStatsService->getCurrentHoldings(
            $portfolioId
        );

        if ($holdings->isEmpty()) {
            return [
                'has_holdings' => false,
                'has_data' => false,
                'growth_count' => 0,
                'portfolio_income_impact' => 0.0,
                'affected_securities' => [],
                'top_contributors' => [],
                'all_rows' => [],
            ];
        }

        $growthRows = $this->securityMetricStatsService
            ->getPositiveDistributionGrowthFromMetrics($holdings);

        if ($growthRows->isEmpty()) {
            return [
                'has_holdings' => true,
                'has_data' => false,
                'growth_count' => 0,
                'portfolio_income_impact' => 0.0,
                'affected_securities' => [],
                'top_contributors' => [],
                'all_rows' => [],
            ];
        }

        $portfolioIncomeImpact = round(
            (float) $growthRows->sum('estimated_income_impact'),
            4
        );

        $topContributors = $growthRows
            ->sortByDesc('estimated_income_impact')
            ->take(3)
            ->values()
            ->toArray();

        $affectedSecurities = $growthRows
            ->pluck('symbol')
            ->filter()
            ->values()
            ->toArray();

        return [
            'has_holdings' => true,
            'has_data' => true,
            'growth_count' => $growthRows->count(),
            'portfolio_income_impact' => $portfolioIncomeImpact,
            'affected_securities' => $affectedSecurities,
            'top_contributors' => $topContributors,
            'all_rows' => $growthRows->values()->toArray(),
        ];
    }

    public function getDistributionDeclineSignalData(int $portfolioId): array
    {
        $holdings = $this->holdingsStatsService->getCurrentHoldings(
            $portfolioId
        );

        if ($holdings->isEmpty()) {
            return [
                'has_holdings' => false,
                'has_data' => false,
                'decline_count' => 0,
                'portfolio_income_impact' => 0.0,
                'affected_securities' => [],
                'top_contributors' => [],
                'all_rows' => [],
            ];
        }

        $declineRows = $this->securityMetricStatsService
            ->getNegativeDistributionGrowthFromMetrics($holdings);

        if ($declineRows->isEmpty()) {
            return [
                'has_holdings' => true,
                'has_data' => false,
                'decline_count' => 0,
                'portfolio_income_impact' => 0.0,
                'affected_securities' => [],
                'top_contributors' => [],
                'all_rows' => [],
            ];
        }

        $portfolioIncomeImpact = round(
            (float) $declineRows->sum('estimated_income_impact'),
            4
        );

        $topContributors = $declineRows
            ->sortBy('estimated_income_impact')
            ->take(3)
            ->values()
            ->toArray();

        $affectedSecurities = $declineRows
            ->pluck('symbol')
            ->filter()
            ->values()
            ->toArray();

        return [
            'has_holdings' => true,
            'has_data' => true,
            'decline_count' => $declineRows->count(),
            'portfolio_income_impact' => $portfolioIncomeImpact,
            'affected_securities' => $affectedSecurities,
            'top_contributors' => $topContributors,
            'all_rows' => $declineRows->values()->toArray(),
        ];
    }

    public function getNavMetricSummary(int $portfolioId): array
    {
        $holdings = $this->holdingsStatsService->getCurrentHoldings(
            $portfolioId
        );

        return $this->securityMetricStatsService->getNavMetricSummary($holdings);
    }
}

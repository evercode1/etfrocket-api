<?php

namespace App\Services\PortfolioStats\Signals;

use App\Services\EtfMetrics\EtfMetricStatsService;
use App\Services\PortfolioStats\PortfolioHoldingsStatsService;

class PortfolioDistributionGrowthSignalService
{
    public function __construct(
        private PortfolioHoldingsStatsService $holdingsStatsService,
        private EtfMetricStatsService $etfMetricStatsService
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
                'affected_etfs' => [],
                'top_contributors' => [],
                'all_rows' => [],
            ];
        }

        $growthRows = $this->etfMetricStatsService
            ->getPositiveDistributionGrowthFromMetrics($holdings);

        if ($growthRows->isEmpty()) {
            return [
                'has_holdings' => true,
                'has_data' => false,
                'growth_count' => 0,
                'portfolio_income_impact' => 0.0,
                'affected_etfs' => [],
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

        $affectedEtfs = $growthRows
            ->pluck('symbol')
            ->filter()
            ->values()
            ->toArray();

        return [
            'has_holdings' => true,
            'has_data' => true,
            'growth_count' => $growthRows->count(),
            'portfolio_income_impact' => $portfolioIncomeImpact,
            'affected_etfs' => $affectedEtfs,
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
                'affected_etfs' => [],
                'top_contributors' => [],
                'all_rows' => [],
            ];
        }

        $declineRows = $this->etfMetricStatsService
            ->getNegativeDistributionGrowthFromMetrics($holdings);

        if ($declineRows->isEmpty()) {
            return [
                'has_holdings' => true,
                'has_data' => false,
                'decline_count' => 0,
                'portfolio_income_impact' => 0.0,
                'affected_etfs' => [],
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

        $affectedEtfs = $declineRows
            ->pluck('symbol')
            ->filter()
            ->values()
            ->toArray();

        return [
            'has_holdings' => true,
            'has_data' => true,
            'decline_count' => $declineRows->count(),
            'portfolio_income_impact' => $portfolioIncomeImpact,
            'affected_etfs' => $affectedEtfs,
            'top_contributors' => $topContributors,
            'all_rows' => $declineRows->values()->toArray(),
        ];
    }

    public function getNavMetricSummary(int $portfolioId): array
    {
        $holdings = $this->holdingsStatsService->getCurrentHoldings(
            $portfolioId
        );

        return $this->etfMetricStatsService->getNavMetricSummary($holdings);
    }
}

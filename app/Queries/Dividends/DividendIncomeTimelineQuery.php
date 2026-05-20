<?php

namespace App\Queries\Dividends;

use App\Services\PortfolioStats\PortfolioDividendStatsService;
use App\Services\PortfolioStats\PortfolioHoldingsStatsService;

class DividendIncomeTimelineQuery
{
    public function __construct(
        private PortfolioHoldingsStatsService $holdingsStatsService,
        private PortfolioDividendStatsService $dividendStatsService
    ) {}

    public function getData(int $portfolioId): array
    {
        $holdings = $this->holdingsStatsService
            ->getCurrentHoldings($portfolioId);

        if ($holdings->isEmpty()) {
            return [];
        }

        return $this->dividendStatsService
            ->getProjectedIncomeTimeline($holdings);
    }
}

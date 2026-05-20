<?php

namespace App\Queries\Dividends;

use App\Services\PortfolioStats\PortfolioDividendStatsService;
use App\Services\PortfolioStats\PortfolioHoldingsStatsService;
use Carbon\Carbon;

class DividendIncomeTimelineQuery
{
    private const FUTURE_MONTHS_TO_PROJECT = 5;

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

        $projectedMonthlyIncome = $this->dividendStatsService
            ->getProjectedMonthlyIncome($holdings);

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
}

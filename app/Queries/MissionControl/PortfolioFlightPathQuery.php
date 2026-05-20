<?php

namespace App\Queries\MissionControl;

use App\Models\PortfolioTransaction;
use App\Services\PortfolioStats\PortfolioHistoricalStatsService;
use Carbon\Carbon;

class PortfolioFlightPathQuery
{
    private const BUY = 1;

    public function __construct(
        private PortfolioHistoricalStatsService $historicalStatsService
    ) {}

    public function getData(int $portfolio_id): array
    {
        $transactions = PortfolioTransaction::where('portfolio_id', $portfolio_id)
            ->where('transaction_type_id', self::BUY)
            ->orderBy('transaction_date')
            ->get();

        if ($transactions->isEmpty()) {
            return [];
        }

        $startDate = Carbon::parse(
            $transactions->min('transaction_date')
        )->startOfMonth();

        $endDate = now()->startOfMonth();

        $points = [];

        $cursor = $startDate->copy();

        while ($cursor->lte($endDate)) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd = $cursor->copy()->endOfMonth();

            if ($monthEnd->gt(now())) {
                $monthEnd = now();
            }

            $points[] = [
                'date' => $monthStart->format('M Y'),
                'value' => $this->historicalStatsService->getPortfolioValueAsOfDate(
                    $portfolio_id,
                    $monthEnd->format('Y-m-d')
                ),
                'income' => $this->historicalStatsService->getDividendIncomeBetweenDates(
                    $portfolio_id,
                    $monthStart->format('Y-m-d'),
                    $monthEnd->format('Y-m-d')
                ),
            ];

            $cursor->addMonth();
        }

        return $points;
    }
}

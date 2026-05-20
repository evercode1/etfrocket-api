<?php

namespace App\Services\Dividends;

use App\Models\Portfolio;
use App\Queries\Dividends\DividendIncomeTimelineQuery;
use App\Queries\Dividends\DividendIntelligenceSummaryQuery;
use App\Queries\Dividends\DividendSignalsQuery;
use App\Queries\Dividends\UpcomingWeeklyDividendsQuery;

class DividendIntelligenceService
{
    public function __construct(
        private DividendIntelligenceSummaryQuery $summaryQuery,
        private DividendIncomeTimelineQuery $incomeTimelineQuery,
        private UpcomingWeeklyDividendsQuery $upcomingWeeklyDividendsQuery,
        private DividendSignalsQuery $dividendSignalsQuery
    ) {}

    public function getData(int $userId, int $portfolioId): array
    {
        $portfolio = Portfolio::where('user_id', $userId)
            ->where('id', $portfolioId)
            ->firstOrFail();

        $summary = $this->summaryQuery->getData($portfolio->id);

        $incomeTimeline = $this->incomeTimelineQuery->getData($portfolio->id);

        $upcomingWeeklyDividends = $this->upcomingWeeklyDividendsQuery->getData(
            $portfolio->id
        );

        $signals = $this->dividendSignalsQuery->getData($portfolio->id);

        return [
            'portfolio' => [
                'id' => $portfolio->id,
                'name' => $portfolio->portfolio_name,
                'has_holdings' => (bool) ($summary['has_holdings'] ?? false),
            ],

            'summary' => [
                'projected_monthly_income' => (float) ($summary['projected_monthly_income'] ?? 0),
                'upcoming_weekly_events_count' => (int) ($summary['upcoming_weekly_events_count'] ?? 0),
                'forward_yield_percentage' => $summary['forward_yield_percentage'] ?? null,
                'dividend_growth_percentage' => $summary['dividend_growth_percentage'] ?? null,
            ],

            'income_timeline' => $incomeTimeline,

            'upcoming_weekly_dividends' => collect($upcomingWeeklyDividends)
                ->take(3)
                ->values()
                ->toArray(),

            'additional_weekly_events_count' => max(
                collect($upcomingWeeklyDividends)->count() - 3,
                0
            ),

            'signals' => $signals,
        ];
    }
}

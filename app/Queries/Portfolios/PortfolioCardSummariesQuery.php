<?php

namespace App\Queries\Portfolios;

use App\Models\Portfolio;
use App\Queries\MissionControl\PortfolioSnapshotQuery;

class PortfolioCardSummariesQuery
{
    public function __construct(
        private PortfolioSnapshotQuery $portfolioSnapshotQuery
    ) {}

    public function getData(int $userId): array
    {
        return Portfolio::where('user_id', $userId)
            ->orderByDesc('is_default')
            ->orderBy('portfolio_name')
            ->get()
            ->map(function ($portfolio) {
                $snapshot = $this->portfolioSnapshotQuery->getData($portfolio->id);

                return [
                    'id' => $portfolio->id,
                    'portfolio_name' => $portfolio->portfolio_name,
                    'is_default' => (bool) $portfolio->is_default,
                    'status_id' => $portfolio->status_id,
                    'portfolio_value' => $snapshot['portfolio_value'] ?? 0,
                    'monthly_income' => $snapshot['monthly_income'] ?? 0,
                    'nav_health' => $snapshot['nav_health'] ?? 'Unknown',
                    'holdings_count' => $snapshot['holdings_count'] ?? 0,
                    'has_holdings' => $snapshot['has_holdings'] ?? false,
                ];
            })
            ->values()
            ->toArray();
    }
}

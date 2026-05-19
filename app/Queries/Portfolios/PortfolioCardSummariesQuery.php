<?php

namespace App\Queries\Portfolios;

use App\Models\Portfolio;
use App\Queries\MissionControl\PortfolioSnapshotQuery;

class PortfolioCardSummariesQuery
{
    public function getData(int $user_id): array
    {
        $portfolios = Portfolio::where('user_id', $user_id)
            ->orderByDesc('is_default')
            ->orderBy('portfolio_name')
            ->get();

        return $portfolios
            ->map(function (Portfolio $portfolio) {

                $snapshot = (new PortfolioSnapshotQuery())->getData($portfolio->id);

                return [
                    'id' => $portfolio->id,
                    'portfolio_name' => $portfolio->portfolio_name,
                    'is_default' => (bool) $portfolio->is_default,
                    'portfolio_value' => $snapshot['portfolio_value'] ?? 0,
                    'monthly_income' => $snapshot['monthly_income'] ?? 0,
                    'nav_health' => $snapshot['nav_health'] ?? 'Unknown',
                    'holdings_count' => isset($snapshot['holdings'])
                        ? count($snapshot['holdings'])
                        : 0,
                ];
            })
            ->values()
            ->toArray();
    }
}

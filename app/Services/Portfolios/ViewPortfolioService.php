<?php

namespace App\Services\Portfolios;

use App\Models\Portfolio;
use App\Queries\MissionControl\PortfolioSnapshotQuery;

class ViewPortfolioService
{
    public function getData(int $userId, int $portfolioId): array
    {
        $portfolio = Portfolio::where('user_id', $userId)
            ->where('id', $portfolioId)
            ->firstOrFail();

        $snapshot = (new PortfolioSnapshotQuery())->getData($portfolio->id);

        $holdings = collect($snapshot['holdings'] ?? [])
            ->map(function (array $holding) use ($snapshot) {

                $portfolioValue = (float) ($snapshot['portfolio_value'] ?? 0);
                $marketValue = (float) ($holding['market_value'] ?? 0);

                return array_merge($holding, [
                    'allocation_percentage' => $portfolioValue > 0
                        ? round(($marketValue / $portfolioValue) * 100, 4)
                        : 0,
                ]);
            })
            ->values()
            ->toArray();

        return [
            'id' => $portfolio->id,
            'portfolio_name' => $portfolio->portfolio_name,
            'is_default' => (bool) $portfolio->is_default,
            'status_id' => $portfolio->status_id,

            'portfolio_value' => $snapshot['portfolio_value'] ?? 0,
            'cost_basis' => $snapshot['cost_basis'] ?? 0,
            'unrealized_gain_loss' => $snapshot['unrealized_gain_loss'] ?? 0,
            'total_return_percentage' => $snapshot['total_return_percentage'] ?? null,
            'monthly_income' => $snapshot['monthly_income'] ?? 0,
            'nav_health' => $snapshot['nav_health'] ?? 'Unknown',

            'holdings' => $holdings,
        ];
    }
}

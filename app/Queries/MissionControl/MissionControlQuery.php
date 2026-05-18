<?php

namespace App\Queries\MissionControl;

use App\Models\Portfolio;
use App\Utilities\Auth;

class MissionControlQuery
{
    public function getData(?int $portfolio_id = null): array
    {
        $user = Auth::user();

        $portfolios = (new PortfolioListQuery())
            ->getData($user->id);

        $selectedPortfolio = $this->resolveSelectedPortfolio(
            $user->id,
            $portfolio_id
        );

        return [

            'portfolios' => $portfolios,

            'selected_portfolio' => $selectedPortfolio,

            'portfolio_snapshot' => $selectedPortfolio
                ? (new PortfolioSnapshotQuery())->getData(
                    $selectedPortfolio->id
                )
                : null,

            'portfolio_flight_path' => $selectedPortfolio
                ? (new PortfolioFlightPathQuery())->getData($selectedPortfolio->id)
                : [],

            'risk_alerts' => (new RiskAlertsQuery())->getData(),

            'watchlist' => (new WatchlistQuery())->getData(),

            'activity' => (new ActivityQuery())->getData(),

        ];
    }

    private function resolveSelectedPortfolio(
        int $user_id,
        ?int $portfolio_id
    ): ?Portfolio {

        /*
        |--------------------------------------------------------------------------
        | Explicit selection from request
        |--------------------------------------------------------------------------
        */

        if ($portfolio_id) {

            $portfolio = Portfolio::where('user_id', $user_id)
                ->where('id', $portfolio_id)
                ->first();

            if ($portfolio) {
                return $portfolio;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Default portfolio fallback
        |--------------------------------------------------------------------------
        */

        $defaultPortfolio = Portfolio::where('user_id', $user_id)
            ->where('is_default', 1)
            ->first();

        if ($defaultPortfolio) {
            return $defaultPortfolio;
        }

        /*
        |--------------------------------------------------------------------------
        | First portfolio fallback
        |--------------------------------------------------------------------------
        */

        return Portfolio::where('user_id', $user_id)
            ->orderBy('id')
            ->first();
    }
}

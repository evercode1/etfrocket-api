<?php

namespace App\Queries\MissionControl;

use App\Models\PerformanceRangeType;
use App\Models\Portfolio;
use App\Models\SecurityMetric;
use App\Models\SecurityPriceHistory;
use App\Services\PortfolioStats\PortfolioDividendStatsService;
use App\Services\PortfolioStats\PortfolioHoldingsStatsService;

class PortfolioSnapshotQuery
{
    public function __construct(
        private PortfolioHoldingsStatsService $holdingsStatsService,
        private PortfolioDividendStatsService $dividendStatsService
    ) {}

    public function getData(int $portfolio_id): ?array
    {

        $portfolio = Portfolio::where('id', $portfolio_id)->first();

        if (! $portfolio) {

            return null;
        }

        $holdings =

            $this->holdingsStatsService
                ->getCurrentHoldings(
                    $portfolio->id
                );

        if ($holdings->isEmpty()) {

            return [

                'portfolio_id' => $portfolio->id,

                'portfolio_name' => $portfolio->portfolio_name,

                'portfolio_value' => 0,

                'cost_basis' => 0,

                'unrealized_gain_loss' => 0,

                'total_return_percentage' => null,

                'monthly_income' => 0,

                'nav_health' => 'No Holdings',

                'holdings' => [],

                'holdings_count' => 0,

                'has_holdings' => false,

                'income_projection' => [],

            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Security IDs
        |--------------------------------------------------------------------------
        */

        $securityIds =

            $holdings
                ->pluck(
                    'security_id'
                )
                ->toArray();

        $dividends =

    $this->dividendStatsService
        ->loadDividendHistory(
            $holdings
        );

        /*
        |--------------------------------------------------------------------------
        | Latest Prices
        |--------------------------------------------------------------------------
        */

        $latestPrices =

            SecurityPriceHistory::select([

                'security_id',

                'close_price',

            ])

                ->whereIn(

                    'security_id',

                    $securityIds

                )

                ->whereIn(

                    'id',

                    SecurityPriceHistory::selectRaw(

                        'MAX(id)'

                    )

                        ->groupBy(
                            'security_id'
                        )

                )

                ->get()

                ->keyBy(
                    'security_id'
                );

        /*
        |--------------------------------------------------------------------------
        | Latest Metrics
        |--------------------------------------------------------------------------
        */

        $latestMetrics =

            SecurityMetric::whereIn(

                'security_id',

                $securityIds

            )

                ->where(

                    'performance_range_type_id',

                    PerformanceRangeType::MAX

                )

                ->get()

                ->keyBy(
                    'security_id'
                );

        $holdingRows = [];

        $portfolioValue = 0;

        $costBasis = 0;

        $projectedMonthlyIncome =

        $this->dividendStatsService
            ->getProjectedMonthlyIncome(

                $holdings,

                $dividends

            );

        foreach ($holdings as $holding) {

            $latestPrice =

                $latestPrices
                    ->get(
                        $holding['security_id']
                    )
                    ?->close_price;

            $latestMetric =

                $latestMetrics
                    ->get(
                        $holding['security_id']
                    );

            $shares =
                (float) $holding['shares'];

            $holdingCostBasis =
                (float) $holding['cost_basis'];

            $currentPrice =
                (float) ($latestPrice ?? 0);

            $marketValue =

                round(

                    $shares * $currentPrice,

                    4

                );

            $portfolioValue +=
                $marketValue;

            $costBasis +=
                $holdingCostBasis;

            $holdingRows[] = [

                'security_id' => $holding['security_id'],

                'symbol' => $holding['symbol'],

                'security_name' => $holding['security_name'],

                'distribution_frequency_id' => $holding['distribution_frequency_id'],

                'shares' => round(
                    $shares,
                    4
                ),

                'cost_basis' => round(
                    $holdingCostBasis,
                    4
                ),

                'latest_price' => $latestPrice

                    ? round(
                        (float) $latestPrice,
                        4
                    )

                    : null,

                'market_value' => $marketValue,

                'estimated_monthly_income' => $this->dividendStatsService
                    ->getProjectedMonthlyIncome(

                        collect([

                            $holding,

                        ]),

                        $dividends

                    ),

                'total_return_percentage' => $latestMetric?->total_return_percentage,

                'nav_erosion_percentage' => $latestMetric?->nav_erosion_percentage,

            ];
        }

        $unrealizedGainLoss =

            round(

                $portfolioValue -

                $costBasis,

                4

            );

        $totalReturnPercentage =

            $costBasis > 0

            ? round(

                (

                    $unrealizedGainLoss /

                    $costBasis

                ) * 100,

                4

            )

            : null;

        return [

            'portfolio_id' => $portfolio->id,

            'portfolio_name' => $portfolio->portfolio_name,

            'portfolio_value' => round(
                $portfolioValue,
                4
            ),

            'cost_basis' => round(
                $costBasis,
                4
            ),

            'unrealized_gain_loss' => $unrealizedGainLoss,

            'total_return_percentage' => $totalReturnPercentage,

            'monthly_income' => round(
                $projectedMonthlyIncome,
                4
            ),

            'nav_health' => $this->getNavHealth(
                $holdingRows
            ),

            'holdings' => $holdingRows,

            'holdings_count' => count(
                $holdingRows
            ),

            'has_holdings' => count(
                $holdingRows
            ) > 0,

            'income_projection' => $this->dividendStatsService
                ->getProjectedIncomeTimeline(

                    $holdings,

                    12,

                    0.08,

                    $dividends

                ),

        ];
    }

    private function getNavHealth(array $holdings): string
    {
        $navValues =

            collect($holdings)

                ->pluck(
                    'nav_erosion_percentage'
                )

                ->filter(

                    fn ($value) => ! is_null(
                        $value
                    )

                );

        if (

            $navValues->isEmpty()

        ) {

            return 'Unknown';
        }

        if (

            $navValues->min() < -10

        ) {

            return 'Watch';
        }

        if (

            $navValues->min() < -3

        ) {

            return 'Mixed';
        }

        return 'Stable';
    }
}

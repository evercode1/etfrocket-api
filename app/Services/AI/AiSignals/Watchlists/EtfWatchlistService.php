<?php

namespace App\Services\AI\AiSignals\Watchlists;

use App\Queries\Comparisons\Metrics\RankSecurityByMetricQuery;

class EtfWatchlistService
{
    public function __construct(
        private RankSecurityByMetricQuery $rankSecurityByMetricQuery
    ) {}

    /**
     * Build ETF watchlists used by AI Signals.
     *
     * These lists represent four different views:
     *
     * - Performance
     * - Momentum
     * - Asset Flows
     * - NAV Quality
     */
    public function getData(): array
    {
        return [

            'top_performers' => $this->topPerformers(),

            'price_movers' => $this->priceMovers(),

            'aum_growth' => $this->aumGrowth(),

            'nav_health' => $this->navHealth(),

        ];
    }

    /**
     * Best total return ETFs over the last 30 days.
     */
    protected function topPerformers(): array
    {
        return $this->rankSecurityByMetricQuery
            ->getData(

                metric: 'total_return_percentage',

                range: '30d',

                metricConfig: [

                    'metric_column' => 'security_metrics.total_return_percentage',

                    'label' => '30 Day Total Return',

                ],

                sortDirection: 'desc',

                limit: 10

            );
    }

    /**
     * Largest price movers over the last 30 days.
     */
    protected function priceMovers(): array
    {
        return $this->rankSecurityByMetricQuery
            ->getData(

                metric: 'price_change_percentage',

                range: '30d',

                metricConfig: [

                    'metric_column' => 'security_metrics.price_change_percentage',

                    'label' => '30 Day Price Change',

                ],

                sortDirection: 'desc',

                limit: 10

            );
    }

    /**
     * ETFs experiencing the strongest asset growth.
     */
    protected function aumGrowth(): array
    {
        return $this->rankSecurityByMetricQuery
            ->getData(

                metric: 'aum_change_percentage',

                range: '30d',

                metricConfig: [

                    'metric_column' => 'security_metrics.aum_change_percentage',

                    'label' => '30 Day AUM Growth',

                ],

                sortDirection: 'desc',

                limit: 10

            );
    }

    /**
     * ETFs with the strongest NAV preservation.
     *
     * Higher values indicate less NAV erosion.
     */
    protected function navHealth(): array
    {
        return $this->rankSecurityByMetricQuery
            ->getData(

                metric: 'nav_erosion_percentage',

                range: '30d',

                metricConfig: [

                    'metric_column' => 'security_metrics.nav_erosion_percentage',

                    'label' => 'NAV Health',

                ],

                sortDirection: 'desc',

                limit: 10

            );
    }
}

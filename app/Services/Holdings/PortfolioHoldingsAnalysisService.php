<?php

namespace App\Services\Holdings;

use App\Models\EtfPriceHistory;
use App\Models\PerformanceRangeType;
use App\Models\Portfolio;
use App\Services\EtfMetrics\EtfMetricStatsService;
use App\Services\PortfolioStats\PortfolioDividendStatsService;
use App\Services\PortfolioStats\PortfolioHoldingsStatsService;
use Illuminate\Support\Collection;

class PortfolioHoldingsAnalysisService
{
    public function __construct(
        private PortfolioHoldingsStatsService $holdingsStatsService,
        private PortfolioDividendStatsService $dividendStatsService,
        private EtfMetricStatsService $metricStatsService
    ) {}

    public function getData(int $userId, int $portfolioId): array
    {
        $portfolio = Portfolio::where('id', $portfolioId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $holdings = $this->holdingsStatsService->getCurrentHoldings($portfolioId);

        if ($holdings->isEmpty()) {
            return [
                'portfolio' => $this->portfolioPayload($portfolio),
                'summary' => $this->emptySummary(),
                'insights' => $this->emptyInsights(),
                'holdings' => [],
                'portfolio_selects' => Portfolio::where('user_id', $userId)

                    ->orderByDesc('is_default')

                    ->orderBy('portfolio_name')

                    ->pluck('portfolio_name', 'id')

                    ->toArray(),
            ];
        }

        $etfIds = $holdings
            ->pluck('etf_id')
            ->values()
            ->toArray();

        $latestPrices = $this->getLatestPrices($etfIds);

        $metricsByEtf = $this->metricStatsService->getMetricsForEtfs(
            $etfIds,
            [
                PerformanceRangeType::THIRTY_DAY,
                PerformanceRangeType::MAX,
            ]
        );

        $rows = $this->buildHoldingRows(
            $holdings,
            $latestPrices,
            $metricsByEtf
        );

        $summary = $this->buildSummary($rows);

        $rows = $this->applyAllocationPercentages($rows, $summary);

        return [
            'portfolio' => $this->portfolioPayload($portfolio),
            'summary' => $summary,
            'insights' => $this->buildInsights($rows),
            'holdings' => $rows->toArray(),
            'portfolio_selects' => Portfolio::where('user_id', $userId)

                ->orderByDesc('is_default')

                ->orderBy('portfolio_name')

                ->pluck('portfolio_name', 'id')

                ->toArray(),
        ];
    }

    private function portfolioPayload(Portfolio $portfolio): array
    {
        return [
            'id' => $portfolio->id,
            'name' => $portfolio->portfolio_name,
        ];
    }

    private function buildHoldingRows(
        Collection $holdings,
        Collection $latestPrices,
        Collection $metricsByEtf
    ): Collection {
        return $holdings
            ->map(function (array $holding) use ($latestPrices, $metricsByEtf) {
                $etfId = (int) $holding['etf_id'];
                $shares = (float) ($holding['shares'] ?? 0);
                $costBasis = (float) ($holding['cost_basis'] ?? 0);

                $latestPrice = $latestPrices->get($etfId);

                $marketValue = is_null($latestPrice)
                    ? 0
                    : $shares * (float) $latestPrice;

                $averageCost = $shares > 0
                    ? $costBasis / $shares
                    : null;

                $unrealizedGainLoss = $marketValue - $costBasis;

                $unrealizedGainLossPercentage = $costBasis > 0
                    ? ($unrealizedGainLoss / $costBasis) * 100
                    : null;

                $monthlyIncome = $this->dividendStatsService
                    ->getProjectedMonthlyIncome(collect([$holding]));

                $yieldOnCostPercentage = $costBasis > 0
                    ? (($monthlyIncome * 12) / $costBasis) * 100
                    : null;

                $metrics = collect($metricsByEtf->get($etfId, collect()));

                $thirtyDayMetric = $metrics->firstWhere(
                    'performance_range_type_id',
                    PerformanceRangeType::THIRTY_DAY
                );

                $maxMetric = $metrics->firstWhere(
                    'performance_range_type_id',
                    PerformanceRangeType::MAX
                );

                $navChangePercentage = $this->metricValue(
                    $maxMetric,
                    'nav_erosion_percentage'
                );

                return [
                    'etf_id' => $etfId,
                    'symbol' => $holding['symbol'] ?? null,
                    'fund_name' => $holding['fund_name'] ?? null,
                    'distribution_frequency_id' => $holding['distribution_frequency_id'] ?? null,
                    'distribution_frequency_name' => $holding['distribution_frequency_name'] ?? null,

                    'shares' => round($shares, 4),
                    'average_cost' => is_null($averageCost) ? null : round($averageCost, 4),
                    'current_price' => is_null($latestPrice) ? null : round((float) $latestPrice, 4),
                    'market_value' => round($marketValue, 4),
                    'cost_basis' => round($costBasis, 4),

                    'unrealized_gain_loss' => round($unrealizedGainLoss, 4),
                    'unrealized_gain_loss_percentage' => is_null($unrealizedGainLossPercentage)
                        ? null
                        : round($unrealizedGainLossPercentage, 4),

                    'estimated_monthly_income' => round($monthlyIncome, 4),
                    'yield_on_cost_percentage' => is_null($yieldOnCostPercentage)
                        ? null
                        : round($yieldOnCostPercentage, 4),

                    'allocation_percentage' => 0,
                    'income_allocation_percentage' => 0,

                    'nav_change_percentage' => $navChangePercentage,
                    'nav_health' => $this->navHealth($navChangePercentage),

                    'aum_flow_percentage' => $this->metricValue(
                        $thirtyDayMetric,
                        'aum_change_percentage'
                    ),
                ];
            })
            ->sortByDesc('market_value')
            ->values();
    }

    private function applyAllocationPercentages(
        Collection $rows,
        array $summary
    ): Collection {
        $totalMarketValue = (float) ($summary['market_value'] ?? 0);
        $totalMonthlyIncome = (float) ($summary['monthly_income'] ?? 0);

        return $rows
            ->map(function (array $row) use ($totalMarketValue, $totalMonthlyIncome) {
                $marketValue = (float) ($row['market_value'] ?? 0);
                $monthlyIncome = (float) ($row['estimated_monthly_income'] ?? 0);

                $row['allocation_percentage'] = $totalMarketValue > 0
                    ? round(($marketValue / $totalMarketValue) * 100, 4)
                    : 0;

                $row['income_allocation_percentage'] = $totalMonthlyIncome > 0
                    ? round(($monthlyIncome / $totalMonthlyIncome) * 100, 4)
                    : 0;

                return $row;
            })
            ->values();
    }

    private function buildSummary(Collection $rows): array
    {
        $marketValue = round((float) $rows->sum('market_value'), 4);
        $costBasis = round((float) $rows->sum('cost_basis'), 4);
        $monthlyIncome = round((float) $rows->sum('estimated_monthly_income'), 4);
        $unrealizedGainLoss = round((float) $rows->sum('unrealized_gain_loss'), 4);

        return [
            'holdings_count' => $rows->count(),
            'market_value' => $marketValue,
            'cost_basis' => $costBasis,
            'monthly_income' => $monthlyIncome,
            'unrealized_gain_loss' => $unrealizedGainLoss,
            'unrealized_gain_loss_percentage' => $costBasis > 0
                ? round(($unrealizedGainLoss / $costBasis) * 100, 4)
                : null,
            'yield_on_cost_percentage' => $costBasis > 0
                ? round((($monthlyIncome * 12) / $costBasis) * 100, 4)
                : null,
        ];
    }

    private function buildInsights(Collection $rows): array
    {
        $largestPosition = $rows->sortByDesc('market_value')->first();
        $topIncomeDriver = $rows->sortByDesc('estimated_monthly_income')->first();
        $highestGain = $rows->sortByDesc('unrealized_gain_loss')->first();

        return [
            'largest_position' => $this->insightRow(
                $largestPosition,
                'allocation_percentage'
            ),
            'top_income_driver' => $this->insightRow(
                $topIncomeDriver,
                'income_allocation_percentage'
            ),
            'highest_gain' => $this->insightRow(
                $highestGain,
                'unrealized_gain_loss'
            ),
        ];
    }

    private function insightRow(?array $row, string $valueField): ?array
    {
        if (! $row) {
            return null;
        }

        return [
            'etf_id' => $row['etf_id'],
            'symbol' => $row['symbol'],
            'value' => $row[$valueField] ?? null,
        ];
    }

    private function getLatestPrices(array $etfIds): Collection
    {
        if (empty($etfIds)) {
            return collect();
        }

        return EtfPriceHistory::query()
            ->whereIn('etf_id', $etfIds)
            ->orderByDesc('price_date')
            ->get()
            ->groupBy('etf_id')
            ->map(fn (Collection $prices) => (float) $prices->first()->close_price);
    }

    private function emptySummary(): array
    {
        return [
            'holdings_count' => 0,
            'market_value' => 0,
            'cost_basis' => 0,
            'monthly_income' => 0,
            'unrealized_gain_loss' => 0,
            'unrealized_gain_loss_percentage' => null,
            'yield_on_cost_percentage' => null,
        ];
    }

    private function emptyInsights(): array
    {
        return [
            'largest_position' => null,
            'top_income_driver' => null,
            'highest_gain' => null,
        ];
    }

    private function metricValue(?object $metric, string $field): ?float
    {
        if (! $metric || is_null($metric->{$field})) {
            return null;
        }

        return round((float) $metric->{$field}, 4);
    }

    private function navHealth(?float $navChangePercentage): string
    {
        if (is_null($navChangePercentage)) {
            return 'Unknown';
        }

        if ($navChangePercentage < -10) {
            return 'Watch';
        }

        if ($navChangePercentage < -3) {
            return 'Mixed';
        }

        return 'Stable';
    }
}

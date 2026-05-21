<?php

namespace App\Services\EtfMetrics;

use App\Models\EtfMetric;
use App\Models\PerformanceRangeType;
use Illuminate\Support\Collection;

class EtfMetricStatsService
{
    public function getMetricsForEtfs(
        array|Collection $etfIds,
        array $performanceRangeTypeIds
    ): Collection {
        $etfIds = collect($etfIds)
            ->map(fn($etfId) => (int) $etfId)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($etfIds) || empty($performanceRangeTypeIds)) {
            return collect();
        }

        return EtfMetric::query()
            ->whereIn('etf_id', $etfIds)
            ->whereIn('performance_range_type_id', $performanceRangeTypeIds)
            ->get()
            ->groupBy('etf_id');
    }

    public function getMetricForEtf(
        int $etfId,
        int $performanceRangeTypeId
    ): ?EtfMetric {
        return EtfMetric::query()
            ->where('etf_id', $etfId)
            ->where('performance_range_type_id', $performanceRangeTypeId)
            ->first();
    }

    public function getDistributionGrowthFromMetrics(Collection $holdings): Collection
    {
        if ($holdings->isEmpty()) {
            return collect();
        }

        $etfIds = $holdings
            ->pluck('etf_id')
            ->map(fn($etfId) => (int) $etfId)
            ->filter()
            ->unique()
            ->values();

        $metricsByEtf = $this->getMetricsForEtfs($etfIds, [
            PerformanceRangeType::THIRTY_DAY,
            PerformanceRangeType::NINETY_DAY,
        ]);

        return $holdings
            ->map(function (array $holding) use ($metricsByEtf) {
                $etfId = (int) $holding['etf_id'];

                $metrics = collect($metricsByEtf->get($etfId, collect()));

                $recentMetric = $metrics
                    ->firstWhere(
                        'performance_range_type_id',
                        PerformanceRangeType::THIRTY_DAY
                    );

                $baselineMetric = $metrics
                    ->firstWhere(
                        'performance_range_type_id',
                        PerformanceRangeType::NINETY_DAY
                    );

                if (! $recentMetric || ! $baselineMetric) {
                    return null;
                }

                $recentAverageDividend = (float) ($recentMetric->average_dividend ?? 0);
                $baselineAverageDividend = (float) ($baselineMetric->average_dividend ?? 0);

                if ($baselineAverageDividend <= 0) {
                    return null;
                }

                $growthPercentage = (
                    ($recentAverageDividend - $baselineAverageDividend)
                    / $baselineAverageDividend
                ) * 100;

                $shares = (float) ($holding['shares'] ?? 0);

                $estimatedIncomeImpact = $shares * (
                    $recentAverageDividend - $baselineAverageDividend
                );

                return [
                    'etf_id' => $etfId,
                    'symbol' => $holding['symbol'] ?? null,
                    'fund_name' => $holding['fund_name'] ?? null,
                    'shares' => round($shares, 4),
                    'recent_average_dividend' => round($recentAverageDividend, 4),
                    'baseline_average_dividend' => round($baselineAverageDividend, 4),
                    'growth_percentage' => round($growthPercentage, 4),
                    'estimated_income_impact' => round($estimatedIncomeImpact, 4),
                    'recent_dividend_count' => (int) ($recentMetric->dividend_count ?? 0),
                    'baseline_dividend_count' => (int) ($baselineMetric->dividend_count ?? 0),
                    'recent_range_type_id' => PerformanceRangeType::THIRTY_DAY,
                    'baseline_range_type_id' => PerformanceRangeType::NINETY_DAY,
                ];
            })
            ->filter()
            ->sortByDesc('estimated_income_impact')
            ->values();
    }

    public function getPositiveDistributionGrowthFromMetrics(
        Collection $holdings
    ): Collection {
        return $this->getDistributionGrowthFromMetrics($holdings)
            ->filter(fn(array $row) => (float) $row['growth_percentage'] > 0)
            ->values();
    }

    public function getNegativeDistributionGrowthFromMetrics(
        Collection $holdings
    ): Collection {
        return $this->getDistributionGrowthFromMetrics($holdings)
            ->filter(fn(array $row) => (float) $row['growth_percentage'] < 0)
            ->values();
    }

    public function getNavMetricSummary(Collection $holdings): array
    {
        if ($holdings->isEmpty()) {
            return [
                'nav_health' => 'No Holdings',
                'worst_nav_erosion_percentage' => null,
                'affected_etfs' => [],
            ];
        }

        $etfIds = $holdings
            ->pluck('etf_id')
            ->map(fn($etfId) => (int) $etfId)
            ->filter()
            ->unique()
            ->values();

        $metricsByEtf = $this->getMetricsForEtfs($etfIds, [
            PerformanceRangeType::MAX,
        ]);

        $rows = $holdings
            ->map(function (array $holding) use ($metricsByEtf) {
                $etfId = (int) $holding['etf_id'];

                $metric = collect($metricsByEtf->get($etfId, collect()))
                    ->firstWhere(
                        'performance_range_type_id',
                        PerformanceRangeType::MAX
                    );

                if (! $metric || is_null($metric->nav_erosion_percentage)) {
                    return null;
                }

                return [
                    'etf_id' => $etfId,
                    'symbol' => $holding['symbol'] ?? null,
                    'nav_erosion_percentage' => (float) $metric->nav_erosion_percentage,
                ];
            })
            ->filter()
            ->values();

        if ($rows->isEmpty()) {
            return [
                'nav_health' => 'Unknown',
                'worst_nav_erosion_percentage' => null,
                'affected_etfs' => [],
            ];
        }

        $worstNavErosion = $rows->min('nav_erosion_percentage');

        $navHealth = match (true) {
            $worstNavErosion < -10 => 'Watch',
            $worstNavErosion < -3 => 'Mixed',
            default => 'Stable',
        };

        return [
            'nav_health' => $navHealth,
            'worst_nav_erosion_percentage' => round((float) $worstNavErosion, 4),
            'affected_etfs' => $rows
                ->filter(fn(array $row) => (float) $row['nav_erosion_percentage'] === (float) $worstNavErosion)
                ->pluck('symbol')
                ->filter()
                ->values()
                ->toArray(),
        ];
    }
}

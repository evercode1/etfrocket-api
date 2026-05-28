<?php

namespace App\Services\SecurityMetrics;

use App\Models\PerformanceRangeType;
use App\Models\SecurityMetric;
use Illuminate\Support\Collection;

class SecurityMetricStatsService
{
    public function getMetricsForSecurities(
        array|Collection $securityIds,
        array $performanceRangeTypeIds
    ): Collection {
        $securityIds = collect($securityIds)
            ->map(fn ($securityId) => (int) $securityId)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($securityIds) || empty($performanceRangeTypeIds)) {
            return collect();
        }

        return SecurityMetric::query()
            ->whereIn('security_id', $securityIds)
            ->whereIn('performance_range_type_id', $performanceRangeTypeIds)
            ->get()
            ->groupBy('security_id');
    }

    public function getMetricForSecurity(
        int $securityId,
        int $performanceRangeTypeId
    ): ?SecurityMetric {
        return SecurityMetric::query()
            ->where('security_id', $securityId)
            ->where('performance_range_type_id', $performanceRangeTypeId)
            ->first();
    }

    public function getDistributionGrowthFromMetrics(Collection $holdings): Collection
    {
        if ($holdings->isEmpty()) {
            return collect();
        }

        $securityIds = $holdings
            ->pluck('security_id')
            ->map(fn ($securityId) => (int) $securityId)
            ->filter()
            ->unique()
            ->values();

        $metricsBySecurity = $this->getMetricsForSecurities($securityIds, [
            PerformanceRangeType::THIRTY_DAY,
            PerformanceRangeType::NINETY_DAY,
        ]);

        return $holdings
            ->map(function (array $holding) use ($metricsBySecurity) {
                $securityId = (int) $holding['security_id'];

                $metrics = collect($metricsBySecurity->get($securityId, collect()));

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
                    'security_id' => $securityId,
                    'symbol' => $holding['symbol'] ?? null,
                    'security_name' => $holding['security_name'] ?? null,
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
            ->filter(fn (array $row) => (float) $row['growth_percentage'] > 0)
            ->values();
    }

    public function getNegativeDistributionGrowthFromMetrics(
        Collection $holdings
    ): Collection {
        return $this->getDistributionGrowthFromMetrics($holdings)
            ->filter(fn (array $row) => (float) $row['growth_percentage'] < 0)
            ->values();
    }

    public function getNavMetricSummary(Collection $holdings): array
    {
        if ($holdings->isEmpty()) {
            return [
                'nav_health' => 'No Holdings',
                'worst_nav_erosion_percentage' => null,
                'affected_securities' => [],
            ];
        }

        $securityIds = $holdings
            ->pluck('security_id')
            ->map(fn ($securityId) => (int) $securityId)
            ->filter()
            ->unique()
            ->values();

        $metricsBySecurity = $this->getMetricsForSecurities($securityIds, [
            PerformanceRangeType::MAX,
        ]);

        $rows = $holdings
            ->map(function (array $holding) use ($metricsBySecurity) {
                $securityId = (int) $holding['security_id'];

                $metric = collect($metricsBySecurity->get($securityId, collect()))
                    ->firstWhere(
                        'performance_range_type_id',
                        PerformanceRangeType::MAX
                    );

                if (! $metric || is_null($metric->nav_erosion_percentage)) {
                    return null;
                }

                return [
                    'security_id' => $securityId,
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
                'affected_securities' => [],
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
            'affected_securities' => $rows
                ->filter(fn (array $row) => (float) $row['nav_erosion_percentage'] === (float) $worstNavErosion)
                ->pluck('symbol')
                ->filter()
                ->values()
                ->toArray(),
        ];
    }
}

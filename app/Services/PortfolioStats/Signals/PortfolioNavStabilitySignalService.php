<?php

namespace App\Services\PortfolioStats\Signals;

use App\Models\EtfMetric;
use App\Models\PerformanceRangeType;
use App\Services\PortfolioStats\PortfolioHoldingsStatsService;
use Illuminate\Support\Collection;

class PortfolioNavStabilitySignalService
{
    private const RANGE_TYPE_ID = PerformanceRangeType::MAX;

    public function __construct(
        private PortfolioHoldingsStatsService $holdingsStatsService
    ) {}

    public function getSignalData(int $portfolioId): array
    {
        $holdings = $this->holdingsStatsService->getCurrentHoldings($portfolioId);

        if ($holdings->isEmpty()) {
            return $this->emptyResponse(false);
        }

        $rows = $this->getNavRows($holdings);

        if ($rows->isEmpty()) {
            return $this->emptyResponse(true);
        }

        $watchRows = $rows
            ->filter(fn (array $row) => (float) $row['nav_erosion_percentage'] < -10)
            ->sortBy('nav_erosion_percentage')
            ->values();

        $mixedRows = $rows
            ->filter(
                fn (array $row) => (float) $row['nav_erosion_percentage'] < -3 &&
                    (float) $row['nav_erosion_percentage'] >= -10
            )
            ->sortBy('nav_erosion_percentage')
            ->values();

        $stableRows = $rows
            ->filter(fn (array $row) => (float) $row['nav_erosion_percentage'] >= -3)
            ->sortByDesc('nav_erosion_percentage')
            ->values();

        $navHealth = match (true) {
            $watchRows->isNotEmpty() => 'Watch',
            $mixedRows->isNotEmpty() => 'Mixed',
            default => 'Stable',
        };

        return [
            'has_holdings' => true,
            'has_data' => true,
            'range_type_id' => self::RANGE_TYPE_ID,
            'nav_health' => $navHealth,
            'stable_count' => $stableRows->count(),
            'mixed_count' => $mixedRows->count(),
            'watch_count' => $watchRows->count(),
            'worst_nav_erosion_percentage' => round((float) $rows->min('nav_erosion_percentage'), 4),
            'watch_list' => $watchRows->take(3)->values()->toArray(),
            'mixed_list' => $mixedRows->take(3)->values()->toArray(),
            'stable_list' => $stableRows->take(3)->values()->toArray(),
            'affected_etfs' => $rows->pluck('symbol')->filter()->values()->toArray(),
            'all_rows' => $rows->values()->toArray(),
        ];
    }

    private function getNavRows(Collection $holdings): Collection
    {
        $etfIds = $holdings
            ->pluck('etf_id')
            ->map(fn ($etfId) => (int) $etfId)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($etfIds)) {
            return collect();
        }

        $metricsByEtf = EtfMetric::query()
            ->whereIn('etf_id', $etfIds)
            ->where('performance_range_type_id', self::RANGE_TYPE_ID)
            ->get()
            ->keyBy('etf_id');

        return $holdings
            ->map(function (array $holding) use ($metricsByEtf) {
                $etfId = (int) $holding['etf_id'];

                $metric = $metricsByEtf->get($etfId);

                if (! $metric || is_null($metric->nav_erosion_percentage)) {
                    return null;
                }

                return [
                    'etf_id' => $etfId,
                    'symbol' => $holding['symbol'] ?? null,
                    'fund_name' => $holding['fund_name'] ?? null,
                    'shares' => round((float) ($holding['shares'] ?? 0), 4),
                    'start_nav' => is_null($metric->start_nav) ? null : round((float) $metric->start_nav, 4),
                    'end_nav' => is_null($metric->end_nav) ? null : round((float) $metric->end_nav, 4),
                    'nav_change' => is_null($metric->nav_change) ? null : round((float) $metric->nav_change, 4),
                    'nav_erosion_percentage' => round((float) $metric->nav_erosion_percentage, 4),
                    'nav_direction_id' => $metric->nav_direction_id ? (int) $metric->nav_direction_id : null,
                    'start_date' => $metric->start_date ? $metric->start_date->toDateString() : null,
                    'end_date' => $metric->end_date ? $metric->end_date->toDateString() : null,
                    'range_type_id' => self::RANGE_TYPE_ID,
                ];
            })
            ->filter()
            ->sortBy('nav_erosion_percentage')
            ->values();
    }

    private function emptyResponse(bool $hasHoldings): array
    {
        return [
            'has_holdings' => $hasHoldings,
            'has_data' => false,
            'range_type_id' => self::RANGE_TYPE_ID,
            'nav_health' => $hasHoldings ? 'Unknown' : 'No Holdings',
            'stable_count' => 0,
            'mixed_count' => 0,
            'watch_count' => 0,
            'worst_nav_erosion_percentage' => null,
            'watch_list' => [],
            'mixed_list' => [],
            'stable_list' => [],
            'affected_etfs' => [],
            'all_rows' => [],
        ];
    }
}

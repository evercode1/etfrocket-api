<?php

namespace App\Services\PortfolioStats\Signals;

use App\Models\EtfMetric;
use App\Models\PerformanceRangeType;
use App\Services\PortfolioStats\PortfolioHoldingsStatsService;
use Illuminate\Support\Collection;

class PortfolioAumGrowthSignalService
{
    private const RANGE_TYPE_ID = PerformanceRangeType::THIRTY_DAY;

    public function __construct(
        private PortfolioHoldingsStatsService $holdingsStatsService
    ) {}

    public function getSignalData(int $portfolioId): array
    {
        $holdings = $this->holdingsStatsService->getCurrentHoldings(
            $portfolioId
        );

        if ($holdings->isEmpty()) {
            return $this->emptyResponse(false);
        }

        $rows = $this->getAumRows($holdings);

        if ($rows->isEmpty()) {
            return $this->emptyResponse(true);
        }

        $positiveRows = $rows
            ->filter(fn(array $row) => (float) $row['aum_change_percentage'] > 0)
            ->sortByDesc('aum_change_percentage')
            ->values();

        $negativeRows = $rows
            ->filter(fn(array $row) => (float) $row['aum_change_percentage'] < 0)
            ->sortBy('aum_change_percentage')
            ->values();

        return [
            'has_holdings' => true,
            'has_data' => true,
            'range_type_id' => self::RANGE_TYPE_ID,
            'positive_flow_count' => $positiveRows->count(),
            'negative_flow_count' => $negativeRows->count(),
            'strongest_inflows' => $positiveRows->take(3)->values()->toArray(),
            'strongest_outflows' => $negativeRows->take(3)->values()->toArray(),
            'affected_etfs' => $rows
                ->pluck('symbol')
                ->filter()
                ->values()
                ->toArray(),
            'all_rows' => $rows->values()->toArray(),
        ];
    }

    private function getAumRows(Collection $holdings): Collection
    {
        $etfIds = $holdings
            ->pluck('etf_id')
            ->map(fn($etfId) => (int) $etfId)
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

                if (! $metric) {
                    return null;
                }

                if (is_null($metric->aum_change_percentage)) {
                    return null;
                }

                return [
                    'etf_id' => $etfId,
                    'symbol' => $holding['symbol'] ?? null,
                    'fund_name' => $holding['fund_name'] ?? null,
                    'shares' => round((float) ($holding['shares'] ?? 0), 4),
                    'start_aum' => is_null($metric->start_aum)
                        ? null
                        : (int) $metric->start_aum,
                    'end_aum' => is_null($metric->end_aum)
                        ? null
                        : (int) $metric->end_aum,
                    'aum_change' => is_null($metric->aum_change)
                        ? null
                        : (int) $metric->aum_change,
                    'aum_change_percentage' => round(
                        (float) $metric->aum_change_percentage,
                        4
                    ),
                    'aum_direction_id' => $metric->aum_direction_id
                        ? (int) $metric->aum_direction_id
                        : null,
                    'start_date' => $metric->start_date
                        ? $metric->start_date->toDateString()
                        : null,

                    'end_date' => $metric->end_date
                        ? $metric->end_date->toDateString()
                        : null,
                    'range_type_id' => self::RANGE_TYPE_ID,
                ];
            })
            ->filter()
            ->sortByDesc('aum_change_percentage')
            ->values();
    }

    private function emptyResponse(bool $hasHoldings): array
    {
        return [
            'has_holdings' => $hasHoldings,
            'has_data' => false,
            'range_type_id' => self::RANGE_TYPE_ID,
            'positive_flow_count' => 0,
            'negative_flow_count' => 0,
            'strongest_inflows' => [],
            'strongest_outflows' => [],
            'affected_etfs' => [],
            'all_rows' => [],
        ];
    }
}

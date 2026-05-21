<?php

namespace App\Services\Comparisons;

use App\Models\EtfPriceHistory;
use App\Models\PerformanceRangeType;
use App\Models\Portfolio;
use App\Services\EtfComparisons\EtfComparisonService;
use App\Services\EtfMetrics\EtfMetricStatsService;
use App\Services\PortfolioStats\PortfolioDividendStatsService;
use App\Services\PortfolioStats\PortfolioHoldingsStatsService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PortfolioCompareService
{
    public function __construct(
        private PortfolioHoldingsStatsService $holdingsStatsService,
        private PortfolioDividendStatsService $dividendStatsService,
        private EtfMetricStatsService $metricStatsService,
        private EtfComparisonService $comparisonService
    ) {}

    public function getData(
        int $userId,
        int $portfolioId,
        array $filters = []
    ): array {
        $portfolio = Portfolio::where('id', $portfolioId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $portfolioSelects = Portfolio::where('user_id', $userId)
            ->orderByDesc('is_default')
            ->orderBy('portfolio_name')
            ->pluck('portfolio_name', 'id')
            ->toArray();

        $holdings = $this->holdingsStatsService->getCurrentHoldings($portfolio->id);

        if ($holdings->isEmpty()) {
            return $this->emptyResponse($portfolio, $portfolioSelects, $filters);
        }

        $maxEtfs = $this->comparisonService->getMaxEtfs();

        $comparisonHoldings = $holdings
            ->take($maxEtfs)
            ->values();

        $etfIds = $comparisonHoldings
            ->pluck('etf_id')
            ->values()
            ->toArray();

        $resolved = $this->comparisonService->resolve([
            'metric' => $filters['metric'] ?? null,
            'range' => $filters['range'] ?? null,
            'etf_ids' => $etfIds,
        ]);

        $metricsByEtf = $this->metricStatsService->getMetricsForEtfs(
            $etfIds,
            [
                PerformanceRangeType::THIRTY_DAY,
                PerformanceRangeType::NINETY_DAY,
                PerformanceRangeType::MAX,
            ]
        );

        $tableRows = $this->buildTableRows($comparisonHoldings, $metricsByEtf);

        return [
            'portfolio' => [
                'id' => $portfolio->id,
                'name' => $portfolio->portfolio_name,
            ],

            'portfolio_selects' => $portfolioSelects,

            'selected' => [
                'metric' => $resolved['metric'],
                'range' => $resolved['range'],
                'days' => $resolved['days'],
            ],

            'options' => $this->buildOptions(),

            'summary' => $this->buildSummary($tableRows),

            'table_rows' => $tableRows->toArray(),

            'chart_rows' => $this->buildChartRows($resolved, $comparisonHoldings),
        ];
    }

    private function buildTableRows(
        Collection $holdings,
        Collection $metricsByEtf
    ): Collection {
        return $holdings
            ->map(function (array $holding) use ($metricsByEtf) {
                $etfId = (int) $holding['etf_id'];

                $metrics = collect($metricsByEtf->get($etfId, collect()));

                $thirtyDayMetric = $metrics->firstWhere(
                    'performance_range_type_id',
                    PerformanceRangeType::THIRTY_DAY
                );

                $ninetyDayMetric = $metrics->firstWhere(
                    'performance_range_type_id',
                    PerformanceRangeType::NINETY_DAY
                );

                $maxMetric = $metrics->firstWhere(
                    'performance_range_type_id',
                    PerformanceRangeType::MAX
                );

                $latestPrice = EtfPriceHistory::where('etf_id', $etfId)
                    ->orderByDesc('price_date')
                    ->value('close_price');

                $shares = (float) ($holding['shares'] ?? 0);
                $price = is_null($latestPrice) ? null : (float) $latestPrice;

                $marketValue = is_null($price)
                    ? null
                    : round($shares * $price, 4);

                $monthlyIncome = $this->dividendStatsService
                    ->getProjectedMonthlyIncome(collect([$holding]));

                return [
                    'etf_id' => $etfId,
                    'symbol' => $holding['symbol'] ?? null,
                    'fund_name' => $holding['fund_name'] ?? null,
                    'shares' => round($shares, 4),
                    'cost_basis' => round((float) ($holding['cost_basis'] ?? 0), 4),
                    'latest_price' => is_null($price) ? null : round($price, 4),
                    'market_value' => $marketValue,
                    'monthly_income' => round($monthlyIncome, 4),

                    'price_change_percentage_30_day' => $this->metricValue(
                        $thirtyDayMetric,
                        'price_change_percentage'
                    ),

                    'aum_change_percentage_30_day' => $this->metricValue(
                        $thirtyDayMetric,
                        'aum_change_percentage'
                    ),

                    'nav_change_percentage_max' => $this->metricValue(
                        $maxMetric,
                        'nav_erosion_percentage'
                    ),

                    'total_return_percentage_90_day' => $this->metricValue(
                        $ninetyDayMetric,
                        'total_return_percentage'
                    ),

                    'total_return_percentage_max' => $this->metricValue(
                        $maxMetric,
                        'total_return_percentage'
                    ),

                    'nav_health' => $this->navHealth(
                        $this->metricValue($maxMetric, 'nav_erosion_percentage')
                    ),
                ];
            })
            ->values();
    }

    private function buildChartRows(array $resolved, Collection $holdings): array
    {
        $startDate = $this->getStartDate($resolved['days']);

        $query = DB::table($resolved['table'])
            ->whereIn('etf_id', $resolved['etf_ids'])
            ->select([
                'etf_id',
                $resolved['date_column'] . ' as metric_date',
                $resolved['value_column'] . ' as metric_value',
            ])
            ->orderBy($resolved['date_column']);

        if ($startDate) {
            $query->whereDate($resolved['date_column'], '>=', $startDate);
        }

        $symbolsByEtfId = $holdings
            ->pluck('symbol', 'etf_id')
            ->toArray();

        return $query
            ->get()
            ->groupBy('metric_date')
            ->map(function (Collection $rows, string $date) use ($symbolsByEtfId) {
                $chartRow = [
                    'date' => Carbon::parse($date)->format('M d'),
                ];

                foreach ($rows as $row) {
                    $symbol = $symbolsByEtfId[$row->etf_id] ?? null;

                    if (! $symbol) {
                        continue;
                    }

                    $chartRow[$symbol] = round((float) $row->metric_value, 4);
                }

                return $chartRow;
            })
            ->values()
            ->toArray();
    }

    private function buildSummary(Collection $tableRows): array
    {
        $bestReturnRow = $tableRows
            ->filter(fn(array $row) => ! is_null($row['total_return_percentage_90_day']))
            ->sortByDesc('total_return_percentage_90_day')
            ->first();

        $strongestNavRow = $tableRows
            ->filter(fn(array $row) => ! is_null($row['nav_change_percentage_max']))
            ->sortByDesc('nav_change_percentage_max')
            ->first();

        return [
            'compared_etfs_count' => $tableRows->count(),

            'best_total_return_symbol' => $bestReturnRow['symbol'] ?? null,
            'best_total_return_percentage' => $bestReturnRow['total_return_percentage_90_day'] ?? null,

            'strongest_nav_symbol' => $strongestNavRow['symbol'] ?? null,
            'strongest_nav_change_percentage' => $strongestNavRow['nav_change_percentage_max'] ?? null,
        ];
    }

    private function buildOptions(): array
    {
        $options = $this->comparisonService->getOptions();

        return [
            'metrics' => collect($options['metrics'] ?? [])
                ->map(fn(array $metric, string $key) => [
                    'label' => $metric['label'] ?? $key,
                    'value' => $key,
                ])
                ->values()
                ->toArray(),

            'ranges' => collect($options['ranges'] ?? [])
                ->map(fn(int|string $days, string $key) => [
                    'label' => strtoupper($key),
                    'value' => $key,
                    'days' => $days,
                ])
                ->values()
                ->toArray(),

            'defaults' => $options['defaults'] ?? [],
        ];
    }

    private function emptyResponse(
        Portfolio $portfolio,
        array $portfolioSelects,
        array $filters
    ): array {
        $metric = $this->comparisonService->resolveMetric($filters['metric'] ?? null);
        $range = $this->comparisonService->resolveRange($filters['range'] ?? null);

        return [
            'portfolio' => [
                'id' => $portfolio->id,
                'name' => $portfolio->portfolio_name,
            ],
            'portfolio_selects' => $portfolioSelects,
            'selected' => [
                'metric' => $metric,
                'range' => $range,
                'days' => $this->comparisonService->getRange($range),
            ],
            'options' => $this->buildOptions(),
            'summary' => [
                'compared_etfs_count' => 0,
                'best_total_return_symbol' => null,
                'best_total_return_percentage' => null,
                'strongest_nav_symbol' => null,
                'strongest_nav_change_percentage' => null,
            ],
            'table_rows' => [],
            'chart_rows' => [],
        ];
    }

    private function getStartDate(int|string $days): ?string
    {
        if ($days === 'max') {
            return null;
        }

        if ($days === 'ytd') {
            return Carbon::now()->startOfYear()->toDateString();
        }

        return Carbon::now()->subDays((int) $days)->toDateString();
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

<?php

namespace App\Services\Comparisons;

use App\Models\PerformanceRangeType;
use App\Models\Portfolio;
use App\Models\SecurityPriceHistory;
use App\Services\PortfolioStats\PortfolioDividendStatsService;
use App\Services\PortfolioStats\PortfolioHoldingsStatsService;
use App\Services\SecurityMetrics\SecurityMetricStatsService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PortfolioCompareService
{
    public function __construct(
        private PortfolioHoldingsStatsService $holdingsStatsService,
        private PortfolioDividendStatsService $dividendStatsService,
        private SecurityMetricStatsService $metricStatsService,
        private SecurityComparisonService $comparisonService
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

        $maxSecurities = $this->comparisonService->getMaxSecurities();

        $comparisonHoldings = $this->getTopHoldingsForComparison(
            $holdings,
            $maxSecurities
        );

        $securityIds = $comparisonHoldings
            ->pluck('security_id')
            ->values()
            ->toArray();

        $resolved = $this->comparisonService->resolve([
            'metric' => $filters['metric'] ?? null,
            'range' => $filters['range'] ?? null,
            'security_ids' => $securityIds,
        ]);

        $metricsBySecurity = $this->metricStatsService->getMetricsForSecurities(
            $securityIds,
            [
                PerformanceRangeType::THIRTY_DAY,
                PerformanceRangeType::NINETY_DAY,
                PerformanceRangeType::MAX,
            ]
        );

        $tableRows = $this->buildTableRows($comparisonHoldings, $metricsBySecurity);

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
            'comparison_limit' => [
                'max_securities' => $maxSecurities,
                'total_holdings_count' => $holdings->count(),
                'included_holdings_count' => $comparisonHoldings->count(),
                'selection_method' => 'Top holdings by current market value',
            ],
        ];
    }

    private function buildTableRows(
        Collection $holdings,
        Collection $metricsBySecurity
    ): Collection {
        return $holdings
            ->map(function (array $holding) use ($metricsBySecurity) {
                $securityId = (int) $holding['security_id'];

                $metrics = collect($metricsBySecurity->get($securityId, collect()));

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

                $shares = (float) ($holding['shares'] ?? 0);

                $price = array_key_exists('latest_price', $holding)
                    ? $holding['latest_price']
                    : SecurityPriceHistory::where('security_id', $securityId)
                        ->orderByDesc('price_date')
                        ->value('close_price');

                $price = is_null($price) ? null : (float) $price;

                $marketValue = array_key_exists('market_value', $holding)
                    ? $holding['market_value']
                    : (
                        is_null($price)
                        ? null
                        : round($shares * $price, 4)
                    );

                $marketValue = is_null($marketValue)
                    ? null
                    : round((float) $marketValue, 4);

                $monthlyIncome = $this->dividendStatsService
                    ->getProjectedMonthlyIncome(collect([$holding]));

                return [
                    'security_id' => $securityId,
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
            ->sortByDesc(fn (array $row) => (float) ($row['market_value'] ?? 0))
            ->values();
    }

    private function buildChartRows(array $resolved, Collection $holdings): array
    {
        $startDate = $this->getStartDate($resolved['days']);

        $query = DB::table($resolved['table'])
            ->whereIn('etf_id', $resolved['etf_ids'])
            ->select([
                'etf_id',
                $resolved['date_column'].' as metric_date',
                $resolved['value_column'].' as metric_value',
            ])
            ->orderBy($resolved['date_column']);

        if ($startDate) {
            $query->whereDate($resolved['date_column'], '>=', $startDate);
        }

        $symbolsBySecurityId = $holdings
            ->pluck('symbol', 'security_id')
            ->toArray();

        return $query
            ->get()
            ->groupBy('metric_date')
            ->map(function (Collection $rows, string $date) use ($symbolsBySecurityId) {
                $chartRow = [
                    'date' => Carbon::parse($date)->format('M d'),
                ];

                foreach ($rows as $row) {
                    $symbol = $symbolsBySecurityId[$row->security_id] ?? null;

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
            ->filter(fn (array $row) => ! is_null($row['total_return_percentage_90_day']))
            ->sortByDesc('total_return_percentage_90_day')
            ->first();

        $strongestNavRow = $tableRows
            ->filter(fn (array $row) => ! is_null($row['nav_change_percentage_max']))
            ->sortByDesc('nav_change_percentage_max')
            ->first();

        return [
            'compared_securities_count' => $tableRows->count(),

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
                ->map(fn (array $metric, string $key) => [
                    'label' => $metric['label'] ?? $key,
                    'value' => $key,
                ])
                ->values()
                ->toArray(),

            'ranges' => collect($options['ranges'] ?? [])
                ->map(fn (int|string $days, string $key) => [
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
                'compared_securities_count' => 0,
                'best_total_return_symbol' => null,
                'best_total_return_percentage' => null,
                'strongest_nav_symbol' => null,
                'strongest_nav_change_percentage' => null,
            ],
            'table_rows' => [],
            'chart_rows' => [],
            'comparison_limit' => [

                'max_securities' => $this->comparisonService->getMaxSecurities(),

                'total_holdings_count' => 0,

                'included_holdings_count' => 0,

                'selection_method' => 'Top holdings by current market value',

            ],
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

    private function getTopHoldingsForComparison(
        Collection $holdings,
        int $maxSecurities
    ): Collection {
        $latestPrices = SecurityPriceHistory::query()
            ->whereIn('security_id', $holdings->pluck('security_id')->toArray())
            ->orderByDesc('price_date')
            ->get()
            ->groupBy('security_id')
            ->map(fn ($prices) => (float) $prices->first()->close_price);

        return $holdings
            ->map(function (array $holding) use ($latestPrices) {
                $price = $latestPrices->get((int) $holding['security_id'], 0);

                $holding['latest_price'] = round((float) $price, 4);
                $holding['market_value'] = round(
                    (float) $holding['shares'] * (float) $price,
                    4
                );

                return $holding;
            })
            ->sortByDesc('market_value')
            ->take($maxSecurities)
            ->values();
    }
}

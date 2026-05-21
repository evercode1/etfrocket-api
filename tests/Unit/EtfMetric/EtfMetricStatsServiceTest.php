<?php

namespace Tests\Unit\Services\EtfMetrics;

use App\Models\Etf;
use App\Models\EtfMetric;
use App\Models\PerformanceRangeType;
use App\Models\Status;
use App\Services\EtfMetrics\EtfMetricStatsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EtfMetricStatsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etf_metrics')->truncate();
        DB::table('etfs')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('etf_metrics')->truncate();
        DB::table('etfs')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_metrics_for_etfs_grouped_by_etf_id(): void
    {
        $firstEtf = $this->createEtf('NVII');
        $secondEtf = $this->createEtf('JEPI');

        $this->createMetric($firstEtf->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.5000',
        ]);

        $this->createMetric($firstEtf->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.4000',
        ]);

        $this->createMetric($secondEtf->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '1.0000',
        ]);

        $metrics = (new EtfMetricStatsService())->getMetricsForEtfs(
            collect([$firstEtf->id, $secondEtf->id]),
            [
                PerformanceRangeType::THIRTY_DAY,
                PerformanceRangeType::NINETY_DAY,
            ]
        );

        $this->assertInstanceOf(Collection::class, $metrics);

        $this->assertTrue($metrics->has($firstEtf->id));
        $this->assertTrue($metrics->has($secondEtf->id));

        $this->assertCount(2, $metrics[$firstEtf->id]);
        $this->assertCount(1, $metrics[$secondEtf->id]);
    }

    public function test_get_metrics_for_etfs_returns_empty_collection_for_empty_etf_ids(): void
    {
        $metrics = (new EtfMetricStatsService())->getMetricsForEtfs(
            [],
            [PerformanceRangeType::THIRTY_DAY]
        );

        $this->assertInstanceOf(Collection::class, $metrics);
        $this->assertTrue($metrics->isEmpty());
    }

    public function test_get_metrics_for_etfs_returns_empty_collection_for_empty_range_ids(): void
    {
        $etf = $this->createEtf('NVII');

        $this->createMetric($etf->id, PerformanceRangeType::THIRTY_DAY);

        $metrics = (new EtfMetricStatsService())->getMetricsForEtfs(
            [$etf->id],
            []
        );

        $this->assertInstanceOf(Collection::class, $metrics);
        $this->assertTrue($metrics->isEmpty());
    }

    public function test_it_returns_single_metric_for_etf_and_range(): void
    {
        $etf = $this->createEtf('NVII');

        $this->createMetric($etf->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.5000',
        ]);

        $metric = (new EtfMetricStatsService())->getMetricForEtf(
            $etf->id,
            PerformanceRangeType::THIRTY_DAY
        );

        $this->assertInstanceOf(EtfMetric::class, $metric);
        $this->assertSame($etf->id, $metric->etf_id);
        $this->assertSame(PerformanceRangeType::THIRTY_DAY, $metric->performance_range_type_id);
        $this->assertSame('0.5000', (string) $metric->average_dividend);
    }

    public function test_get_metric_for_etf_returns_null_when_missing(): void
    {
        $etf = $this->createEtf('NVII');

        $metric = (new EtfMetricStatsService())->getMetricForEtf(
            $etf->id,
            PerformanceRangeType::THIRTY_DAY
        );

        $this->assertNull($metric);
    }

    public function test_it_calculates_distribution_growth_from_metrics(): void
    {
        $firstEtf = $this->createEtf('NVII');
        $secondEtf = $this->createEtf('XDTE');

        $this->createMetric($firstEtf->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.6000',
            'dividend_count' => 4,
        ]);

        $this->createMetric($firstEtf->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.5000',
            'dividend_count' => 12,
        ]);

        $this->createMetric($secondEtf->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '1.2000',
            'dividend_count' => 4,
        ]);

        $this->createMetric($secondEtf->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '1.0000',
            'dividend_count' => 12,
        ]);

        $holdings = collect([
            [
                'etf_id' => $firstEtf->id,
                'symbol' => 'NVII',
                'fund_name' => 'NVII Test ETF',
                'shares' => 100,
            ],
            [
                'etf_id' => $secondEtf->id,
                'symbol' => 'XDTE',
                'fund_name' => 'XDTE Test ETF',
                'shares' => 25,
            ],
        ]);

        $results = (new EtfMetricStatsService())
            ->getDistributionGrowthFromMetrics($holdings);

        $this->assertCount(2, $results);

        $firstResult = $results->firstWhere('symbol', 'NVII');

        $this->assertSame($firstEtf->id, $firstResult['etf_id']);
        $this->assertSame('NVII', $firstResult['symbol']);
        $this->assertSame('NVII Test ETF', $firstResult['fund_name']);
        $this->assertSame(100.0, $firstResult['shares']);
        $this->assertSame(0.6, $firstResult['recent_average_dividend']);
        $this->assertSame(0.5, $firstResult['baseline_average_dividend']);
        $this->assertSame(20.0, $firstResult['growth_percentage']);
        $this->assertSame(10.0, $firstResult['estimated_income_impact']);
        $this->assertSame(4, $firstResult['recent_dividend_count']);
        $this->assertSame(12, $firstResult['baseline_dividend_count']);
        $this->assertSame(PerformanceRangeType::THIRTY_DAY, $firstResult['recent_range_type_id']);
        $this->assertSame(PerformanceRangeType::NINETY_DAY, $firstResult['baseline_range_type_id']);
    }

    public function test_distribution_growth_results_are_sorted_by_income_impact_descending(): void
    {
        $smallImpactEtf = $this->createEtf('SMOL');
        $largeImpactEtf = $this->createEtf('BIGG');

        $this->createMetric($smallImpactEtf->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '1.1000',
        ]);

        $this->createMetric($smallImpactEtf->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '1.0000',
        ]);

        $this->createMetric($largeImpactEtf->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.6000',
        ]);

        $this->createMetric($largeImpactEtf->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.5000',
        ]);

        $holdings = collect([
            [
                'etf_id' => $smallImpactEtf->id,
                'symbol' => 'SMOL',
                'shares' => 10,
            ],
            [
                'etf_id' => $largeImpactEtf->id,
                'symbol' => 'BIGG',
                'shares' => 500,
            ],
        ]);

        $results = (new EtfMetricStatsService())
            ->getDistributionGrowthFromMetrics($holdings);

        $this->assertSame('BIGG', $results[0]['symbol']);
        $this->assertSame(50.0, $results[0]['estimated_income_impact']);

        $this->assertSame('SMOL', $results[1]['symbol']);
        $this->assertSame(1.0, $results[1]['estimated_income_impact']);
    }

    public function test_distribution_growth_excludes_holdings_missing_required_metrics(): void
    {
        $completeEtf = $this->createEtf('GOOD');
        $missingBaselineEtf = $this->createEtf('MISS');

        $this->createMetric($completeEtf->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.6000',
        ]);

        $this->createMetric($completeEtf->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.5000',
        ]);

        $this->createMetric($missingBaselineEtf->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.8000',
        ]);

        $holdings = collect([
            [
                'etf_id' => $completeEtf->id,
                'symbol' => 'GOOD',
                'shares' => 100,
            ],
            [
                'etf_id' => $missingBaselineEtf->id,
                'symbol' => 'MISS',
                'shares' => 100,
            ],
        ]);

        $results = (new EtfMetricStatsService())
            ->getDistributionGrowthFromMetrics($holdings);

        $this->assertCount(1, $results);
        $this->assertSame('GOOD', $results[0]['symbol']);
    }

    public function test_distribution_growth_excludes_holdings_with_zero_baseline_average_dividend(): void
    {
        $etf = $this->createEtf('ZERO');

        $this->createMetric($etf->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.6000',
        ]);

        $this->createMetric($etf->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.0000',
        ]);

        $holdings = collect([
            [
                'etf_id' => $etf->id,
                'symbol' => 'ZERO',
                'shares' => 100,
            ],
        ]);

        $results = (new EtfMetricStatsService())
            ->getDistributionGrowthFromMetrics($holdings);

        $this->assertTrue($results->isEmpty());
    }

    public function test_it_returns_positive_distribution_growth_only(): void
    {
        $positiveEtf = $this->createEtf('UP');
        $negativeEtf = $this->createEtf('DOWN');

        $this->createMetric($positiveEtf->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.6000',
        ]);

        $this->createMetric($positiveEtf->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.5000',
        ]);

        $this->createMetric($negativeEtf->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.4000',
        ]);

        $this->createMetric($negativeEtf->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.5000',
        ]);

        $holdings = collect([
            [
                'etf_id' => $positiveEtf->id,
                'symbol' => 'UP',
                'shares' => 100,
            ],
            [
                'etf_id' => $negativeEtf->id,
                'symbol' => 'DOWN',
                'shares' => 100,
            ],
        ]);

        $results = (new EtfMetricStatsService())
            ->getPositiveDistributionGrowthFromMetrics($holdings);

        $this->assertCount(1, $results);
        $this->assertSame('UP', $results[0]['symbol']);
        $this->assertSame(20.0, $results[0]['growth_percentage']);
    }

    public function test_it_returns_negative_distribution_growth_only(): void
    {
        $positiveEtf = $this->createEtf('UP');
        $negativeEtf = $this->createEtf('DOWN');

        $this->createMetric($positiveEtf->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.6000',
        ]);

        $this->createMetric($positiveEtf->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.5000',
        ]);

        $this->createMetric($negativeEtf->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.4000',
        ]);

        $this->createMetric($negativeEtf->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.5000',
        ]);

        $holdings = collect([
            [
                'etf_id' => $positiveEtf->id,
                'symbol' => 'UP',
                'shares' => 100,
            ],
            [
                'etf_id' => $negativeEtf->id,
                'symbol' => 'DOWN',
                'shares' => 100,
            ],
        ]);

        $results = (new EtfMetricStatsService())
            ->getNegativeDistributionGrowthFromMetrics($holdings);

        $this->assertCount(1, $results);
        $this->assertSame('DOWN', $results[0]['symbol']);
        $this->assertSame(-20.0, $results[0]['growth_percentage']);
    }

    public function test_it_returns_no_holdings_nav_summary_when_holdings_are_empty(): void
    {
        $summary = (new EtfMetricStatsService())->getNavMetricSummary(
            collect()
        );

        $this->assertSame('No Holdings', $summary['nav_health']);
        $this->assertNull($summary['worst_nav_erosion_percentage']);
        $this->assertSame([], $summary['affected_etfs']);
    }

    public function test_it_returns_unknown_nav_summary_when_no_nav_metrics_exist(): void
    {
        $etf = $this->createEtf('NVII');

        $summary = (new EtfMetricStatsService())->getNavMetricSummary(
            collect([
                [
                    'etf_id' => $etf->id,
                    'symbol' => 'NVII',
                    'shares' => 100,
                ],
            ])
        );

        $this->assertSame('Unknown', $summary['nav_health']);
        $this->assertNull($summary['worst_nav_erosion_percentage']);
        $this->assertSame([], $summary['affected_etfs']);
    }

    public function test_it_returns_stable_nav_summary(): void
    {
        $etf = $this->createEtf('NVII');

        $this->createMetric($etf->id, PerformanceRangeType::MAX, [
            'nav_erosion_percentage' => '-2.0000',
        ]);

        $summary = (new EtfMetricStatsService())->getNavMetricSummary(
            collect([
                [
                    'etf_id' => $etf->id,
                    'symbol' => 'NVII',
                    'shares' => 100,
                ],
            ])
        );

        $this->assertSame('Stable', $summary['nav_health']);
        $this->assertSame(-2.0, $summary['worst_nav_erosion_percentage']);
        $this->assertSame(['NVII'], $summary['affected_etfs']);
    }

    public function test_it_returns_mixed_nav_summary(): void
    {
        $firstEtf = $this->createEtf('GOOD');
        $secondEtf = $this->createEtf('MIXD');

        $this->createMetric($firstEtf->id, PerformanceRangeType::MAX, [
            'nav_erosion_percentage' => '-1.0000',
        ]);

        $this->createMetric($secondEtf->id, PerformanceRangeType::MAX, [
            'nav_erosion_percentage' => '-5.0000',
        ]);

        $summary = (new EtfMetricStatsService())->getNavMetricSummary(
            collect([
                [
                    'etf_id' => $firstEtf->id,
                    'symbol' => 'GOOD',
                    'shares' => 100,
                ],
                [
                    'etf_id' => $secondEtf->id,
                    'symbol' => 'MIXD',
                    'shares' => 100,
                ],
            ])
        );

        $this->assertSame('Mixed', $summary['nav_health']);
        $this->assertSame(-5.0, $summary['worst_nav_erosion_percentage']);
        $this->assertSame(['MIXD'], $summary['affected_etfs']);
    }

    public function test_it_returns_watch_nav_summary(): void
    {
        $firstEtf = $this->createEtf('GOOD');
        $secondEtf = $this->createEtf('BAD');

        $this->createMetric($firstEtf->id, PerformanceRangeType::MAX, [
            'nav_erosion_percentage' => '-1.0000',
        ]);

        $this->createMetric($secondEtf->id, PerformanceRangeType::MAX, [
            'nav_erosion_percentage' => '-12.0000',
        ]);

        $summary = (new EtfMetricStatsService())->getNavMetricSummary(
            collect([
                [
                    'etf_id' => $firstEtf->id,
                    'symbol' => 'GOOD',
                    'shares' => 100,
                ],
                [
                    'etf_id' => $secondEtf->id,
                    'symbol' => 'BAD',
                    'shares' => 100,
                ],
            ])
        );

        $this->assertSame('Watch', $summary['nav_health']);
        $this->assertSame(-12.0, $summary['worst_nav_erosion_percentage']);
        $this->assertSame(['BAD'], $summary['affected_etfs']);
    }

    private function createEtf(string $symbol): Etf
    {
        return Etf::factory()->create([
            'symbol' => $symbol,
            'fund_name' => "{$symbol} Test ETF",
            'status_id' => Status::ACTIVE,
        ]);
    }

    private function createMetric(
        int $etfId,
        int $performanceRangeTypeId,
        array $overrides = []
    ): EtfMetric {
        return EtfMetric::factory()->create(array_merge([
            'etf_id' => $etfId,
            'performance_range_type_id' => $performanceRangeTypeId,
            'start_date' => '2026-01-01',
            'end_date' => '2026-05-01',
            'start_price' => '10.0000',
            'end_price' => '12.0000',
            'price_change' => '2.0000',
            'price_change_percentage' => '20.0000',
            'dividends_paid' => '1.0000',
            'dividend_count' => 1,
            'average_dividend' => '1.0000',
            'total_return_percentage' => '25.0000',
            'start_nav' => '10.0000',
            'end_nav' => '10.0000',
            'nav_change' => '0.0000',
            'nav_erosion_percentage' => '0.0000',
            'start_aum' => 1000000,
            'end_aum' => 1100000,
            'aum_change' => 100000,
            'aum_change_percentage' => '10.0000',
            'calculated_at' => now(),
        ], $overrides));
    }
}

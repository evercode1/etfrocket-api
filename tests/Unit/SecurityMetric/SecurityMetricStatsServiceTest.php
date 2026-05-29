<?php

namespace Tests\Unit\SecurityMetric;

use App\Models\PerformanceRangeType;
use App\Models\Security;
use App\Models\SecurityMetric;
use App\Models\Status;
use App\Services\SecurityMetrics\SecurityMetricStatsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecurityMetricStatsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_metrics')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('security_metrics')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_metrics_for_securities_grouped_by_security_id(): void
    {
        $firstSecurity = $this->createSecurity('NVII');
        $secondSecurity = $this->createSecurity('JEPI');

        $this->createMetric($firstSecurity->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.5000',
        ]);

        $this->createMetric($firstSecurity->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.4000',
        ]);

        $this->createMetric($secondSecurity->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '1.0000',
        ]);

        $metrics = (new SecurityMetricStatsService)->getMetricsForSecurities(
            collect([$firstSecurity->id, $secondSecurity->id]),
            [
                PerformanceRangeType::THIRTY_DAY,
                PerformanceRangeType::NINETY_DAY,
            ]
        );

        $this->assertInstanceOf(Collection::class, $metrics);

        $this->assertTrue($metrics->has($firstSecurity->id));
        $this->assertTrue($metrics->has($secondSecurity->id));

        $this->assertCount(2, $metrics[$firstSecurity->id]);
        $this->assertCount(1, $metrics[$secondSecurity->id]);
    }

    public function test_get_metrics_for_securities_returns_empty_collection_for_empty_security_ids(): void
    {
        $metrics = (new SecurityMetricStatsService)->getMetricsForSecurities(
            [],
            [PerformanceRangeType::THIRTY_DAY]
        );

        $this->assertInstanceOf(Collection::class, $metrics);
        $this->assertTrue($metrics->isEmpty());
    }

    public function test_get_metrics_for_securities_returns_empty_collection_for_empty_range_ids(): void
    {
        $security = $this->createSecurity('NVII');

        $this->createMetric($security->id, PerformanceRangeType::THIRTY_DAY);

        $metrics = (new SecurityMetricStatsService)->getMetricsForSecurities(
            [$security->id],
            []
        );

        $this->assertInstanceOf(Collection::class, $metrics);
        $this->assertTrue($metrics->isEmpty());
    }

    public function test_it_returns_single_metric_for_security_and_range(): void
    {
        $security = $this->createSecurity('NVII');

        $this->createMetric($security->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.5000',
        ]);

        $metric = (new SecurityMetricStatsService)->getMetricForSecurity(
            $security->id,
            PerformanceRangeType::THIRTY_DAY
        );

        $this->assertInstanceOf(SecurityMetric::class, $metric);
        $this->assertSame($security->id, $metric->security_id);
        $this->assertSame(PerformanceRangeType::THIRTY_DAY, $metric->performance_range_type_id);
        $this->assertSame('0.5000', (string) $metric->average_dividend);
    }

    public function test_get_metric_for_security_returns_null_when_missing(): void
    {
        $security = $this->createSecurity('NVII');

        $metric = (new SecurityMetricStatsService)->getMetricForSecurity(
            $security->id,
            PerformanceRangeType::THIRTY_DAY
        );

        $this->assertNull($metric);
    }

    public function test_it_calculates_distribution_growth_from_metrics(): void
    {
        $firstSecurity = $this->createSecurity('NVII');
        $secondSecurity = $this->createSecurity('XDTE');

        $this->createMetric($firstSecurity->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.6000',
            'dividend_count' => 4,
        ]);

        $this->createMetric($firstSecurity->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.5000',
            'dividend_count' => 12,
        ]);

        $this->createMetric($secondSecurity->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '1.2000',
            'dividend_count' => 4,
        ]);

        $this->createMetric($secondSecurity->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '1.0000',
            'dividend_count' => 12,
        ]);

        $holdings = collect([
            [
                'security_id' => $firstSecurity->id,
                'symbol' => 'NVII',
                'security_name' => 'NVII_name',
                'shares' => 100,
            ],
            [
                'security_id' => $secondSecurity->id,
                'symbol' => 'XDTE',
                'security_name' => 'XDTE_name',
                'shares' => 25,
            ],
        ]);

        $results = (new SecurityMetricStatsService)
            ->getDistributionGrowthFromMetrics($holdings);

        $this->assertCount(2, $results);

        $firstResult = $results->firstWhere('symbol', 'NVII');

        $this->assertSame($firstSecurity->id, $firstResult['security_id']);
        $this->assertSame('NVII', $firstResult['symbol']);
        $this->assertSame('NVII_name', $firstResult['security_name']);
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
        $smallImpactSecurity = $this->createSecurity('SMOL');
        $largeImpactSecurity = $this->createSecurity('BIGG');

        $this->createMetric($smallImpactSecurity->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '1.1000',
        ]);

        $this->createMetric($smallImpactSecurity->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '1.0000',
        ]);

        $this->createMetric($largeImpactSecurity->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.6000',
        ]);

        $this->createMetric($largeImpactSecurity->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.5000',
        ]);

        $holdings = collect([
            [
                'security_id' => $smallImpactSecurity->id,
                'symbol' => 'SMOL',
                'shares' => 10,
            ],
            [
                'security_id' => $largeImpactSecurity->id,
                'symbol' => 'BIGG',
                'shares' => 500,
            ],
        ]);

        $results = (new SecurityMetricStatsService)
            ->getDistributionGrowthFromMetrics($holdings);

        $this->assertSame('BIGG', $results[0]['symbol']);
        $this->assertSame(50.0, $results[0]['estimated_income_impact']);

        $this->assertSame('SMOL', $results[1]['symbol']);
        $this->assertSame(1.0, $results[1]['estimated_income_impact']);
    }

    public function test_distribution_growth_excludes_holdings_missing_required_metrics(): void
    {
        $completeSecurity = $this->createSecurity('GOOD');
        $missingBaselineSecurity = $this->createSecurity('MISS');

        $this->createMetric($completeSecurity->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.6000',
        ]);

        $this->createMetric($completeSecurity->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.5000',
        ]);

        $this->createMetric($missingBaselineSecurity->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.8000',
        ]);

        $holdings = collect([
            [
                'security_id' => $completeSecurity->id,
                'symbol' => 'GOOD',
                'shares' => 100,
            ],
            [
                'security_id' => $missingBaselineSecurity->id,
                'symbol' => 'MISS',
                'shares' => 100,
            ],
        ]);

        $results = (new SecurityMetricStatsService)
            ->getDistributionGrowthFromMetrics($holdings);

        $this->assertCount(1, $results);
        $this->assertSame('GOOD', $results[0]['symbol']);
    }

    public function test_distribution_growth_excludes_holdings_with_zero_baseline_average_dividend(): void
    {
        $security = $this->createSecurity('ZERO');

        $this->createMetric($security->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.6000',
        ]);

        $this->createMetric($security->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.0000',
        ]);

        $holdings = collect([
            [
                'security_id' => $security->id,
                'symbol' => 'ZERO',
                'shares' => 100,
            ],
        ]);

        $results = (new SecurityMetricStatsService)
            ->getDistributionGrowthFromMetrics($holdings);

        $this->assertTrue($results->isEmpty());
    }

    public function test_it_returns_positive_distribution_growth_only(): void
    {
        $positiveSecurity = $this->createSecurity('UP');
        $negativeSecurity = $this->createSecurity('DOWN');

        $this->createMetric($positiveSecurity->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.6000',
        ]);

        $this->createMetric($positiveSecurity->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.5000',
        ]);

        $this->createMetric($negativeSecurity->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.4000',
        ]);

        $this->createMetric($negativeSecurity->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.5000',
        ]);

        $holdings = collect([
            [
                'security_id' => $positiveSecurity->id,
                'symbol' => 'UP',
                'shares' => 100,
            ],
            [
                'security_id' => $negativeSecurity->id,
                'symbol' => 'DOWN',
                'shares' => 100,
            ],
        ]);

        $results = (new SecurityMetricStatsService)
            ->getPositiveDistributionGrowthFromMetrics($holdings);

        $this->assertCount(1, $results);
        $this->assertSame('UP', $results[0]['symbol']);
        $this->assertSame(20.0, $results[0]['growth_percentage']);
    }

    public function test_it_returns_negative_distribution_growth_only(): void
    {
        $positiveSecurity = $this->createSecurity('UP');
        $negativeSecurity = $this->createSecurity('DOWN');

        $this->createMetric($positiveSecurity->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.6000',
        ]);

        $this->createMetric($positiveSecurity->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.5000',
        ]);

        $this->createMetric($negativeSecurity->id, PerformanceRangeType::THIRTY_DAY, [
            'average_dividend' => '0.4000',
        ]);

        $this->createMetric($negativeSecurity->id, PerformanceRangeType::NINETY_DAY, [
            'average_dividend' => '0.5000',
        ]);

        $holdings = collect([
            [
                'security_id' => $positiveSecurity->id,
                'symbol' => 'UP',
                'shares' => 100,
            ],
            [
                'security_id' => $negativeSecurity->id,
                'symbol' => 'DOWN',
                'shares' => 100,
            ],
        ]);

        $results = (new SecurityMetricStatsService)
            ->getNegativeDistributionGrowthFromMetrics($holdings);

        $this->assertCount(1, $results);
        $this->assertSame('DOWN', $results[0]['symbol']);
        $this->assertSame(-20.0, $results[0]['growth_percentage']);
    }

    public function test_it_returns_no_holdings_nav_summary_when_holdings_are_empty(): void
    {
        $summary = (new SecurityMetricStatsService)->getNavMetricSummary(
            collect()
        );

        $this->assertSame('No Holdings', $summary['nav_health']);
        $this->assertNull($summary['worst_nav_erosion_percentage']);
        $this->assertSame([], $summary['affected_securities']);
    }

    public function test_it_returns_unknown_nav_summary_when_no_nav_metrics_exist(): void
    {
        $security = $this->createSecurity('NVII');

        $summary = (new SecurityMetricStatsService)->getNavMetricSummary(
            collect([
                [
                    'security_id' => $security->id,
                    'symbol' => 'NVII',
                    'shares' => 100,
                ],
            ])
        );

        $this->assertSame('Unknown', $summary['nav_health']);
        $this->assertNull($summary['worst_nav_erosion_percentage']);
        $this->assertSame([], $summary['affected_securities']);
    }

    public function test_it_returns_stable_nav_summary(): void
    {
        $security = $this->createSecurity('NVII');

        $this->createMetric($security->id, PerformanceRangeType::MAX, [
            'nav_erosion_percentage' => '-2.0000',
        ]);

        $summary = (new SecurityMetricStatsService)->getNavMetricSummary(
            collect([
                [
                    'security_id' => $security->id,
                    'symbol' => 'NVII',
                    'shares' => 100,
                ],
            ])
        );

        $this->assertSame('Stable', $summary['nav_health']);
        $this->assertSame(-2.0, $summary['worst_nav_erosion_percentage']);
        $this->assertSame(['NVII'], $summary['affected_securities']);
    }

    public function test_it_returns_mixed_nav_summary(): void
    {
        $firstSecurity = $this->createSecurity('GOOD');
        $secondSecurity = $this->createSecurity('MIXD');

        $this->createMetric($firstSecurity->id, PerformanceRangeType::MAX, [
            'nav_erosion_percentage' => '-1.0000',
        ]);

        $this->createMetric($secondSecurity->id, PerformanceRangeType::MAX, [
            'nav_erosion_percentage' => '-5.0000',
        ]);

        $summary = (new SecurityMetricStatsService)->getNavMetricSummary(
            collect([
                [
                    'security_id' => $firstSecurity->id,
                    'symbol' => 'GOOD',
                    'shares' => 100,
                ],
                [
                    'security_id' => $secondSecurity->id,
                    'symbol' => 'MIXD',
                    'shares' => 100,
                ],
            ])
        );

        $this->assertSame('Mixed', $summary['nav_health']);
        $this->assertSame(-5.0, $summary['worst_nav_erosion_percentage']);
        $this->assertSame(['MIXD'], $summary['affected_securities']);
    }

    public function test_it_returns_watch_nav_summary(): void
    {
        $firstSecurity = $this->createSecurity('GOOD');
        $secondSecurity = $this->createSecurity('BAD');

        $this->createMetric($firstSecurity->id, PerformanceRangeType::MAX, [
            'nav_erosion_percentage' => '-1.0000',
        ]);

        $this->createMetric($secondSecurity->id, PerformanceRangeType::MAX, [
            'nav_erosion_percentage' => '-12.0000',
        ]);

        $summary = (new SecurityMetricStatsService)->getNavMetricSummary(
            collect([
                [
                    'security_id' => $firstSecurity->id,
                    'symbol' => 'GOOD',
                    'shares' => 100,
                ],
                [
                    'security_id' => $secondSecurity->id,
                    'symbol' => 'BAD',
                    'shares' => 100,
                ],
            ])
        );

        $this->assertSame('Watch', $summary['nav_health']);
        $this->assertSame(-12.0, $summary['worst_nav_erosion_percentage']);
        $this->assertSame(['BAD'], $summary['affected_securities']);
    }

    private function createSecurity(string $symbol): Security
    {
        return Security::factory()->create([
            'symbol' => $symbol,

            'status_id' => Status::ACTIVE,
        ]);
    }

    private function createMetric(
        int $securityId,
        int $performanceRangeTypeId,
        array $overrides = []
    ): SecurityMetric {
        return SecurityMetric::factory()->create(array_merge([
            'security_id' => $securityId,
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

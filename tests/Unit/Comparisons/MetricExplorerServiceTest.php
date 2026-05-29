<?php

namespace Tests\Unit\Comparisons;

use App\Models\PerformanceRangeType;
use App\Models\Security;
use App\Models\SecurityMetric;
use App\Queries\Comparisons\Metrics\RankSecurityByMetricQuery;
use App\Services\Comparisons\MetricExplorerService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class MetricExplorerServiceTest extends TestCase
{
    private MetricExplorerService $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_metrics')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();

        $this->service =
            new MetricExplorerService(

                new RankSecurityByMetricQuery

            );
    }

    protected function tearDown(): void
    {
        DB::table('security_metrics')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_metric_explorer_payload()
    {
        $security = $this->createSecurity('CHPY');

        SecurityMetric::factory()->create([

            'security_id' => $security->id,

            'performance_range_type_id' => PerformanceRangeType::NINETY_DAY,

            'price_change_percentage' => 22.50,

            'total_return_percentage' => 18.40,

            'aum_change_percentage' => 10.25,

            'nav_erosion_percentage' => 2.50,

        ]);

        $data = $this->service->getData();

        $this->assertEquals(
            'price_growth',
            $data['summary']['metric']
        );

        $this->assertEquals(
            '90d',
            $data['summary']['range']
        );

        $this->assertEquals(
            'desc',
            $data['summary']['sort_direction']
        );

        $this->assertEquals(
            1,
            $data['summary']['results_count']
        );

        $this->assertCount(
            1,
            $data['spotlight']
        );

        $this->assertCount(
            1,
            $data['table_rows']
        );

        $this->assertEquals(
            'CHPY',
            $data['table_rows'][0]['symbol']
        );

        $this->assertEquals(
            22.50,
            $data['table_rows'][0]['metric_value']
        );
    }

    public function test_it_limits_spotlight_to_three_rows()
    {
        foreach (range(1, 5) as $index) {

            $security = $this->createSecurity(
                "SEC{$index}"
            );

            SecurityMetric::factory()->create([

                'security_id' => $security->id,

                'performance_range_type_id' => PerformanceRangeType::NINETY_DAY,

                'price_change_percentage' => 100 - $index,

            ]);
        }

        $data = $this->service->getData();

        $this->assertCount(
            3,
            $data['spotlight']
        );
    }

    public function test_it_uses_custom_metric()
    {
        $security = $this->createSecurity('CHPY');

        SecurityMetric::factory()->create([

            'security_id' => $security->id,

            'performance_range_type_id' => PerformanceRangeType::NINETY_DAY,

            'aum_change_percentage' => 88.88,

        ]);

        $data = $this->service->getData(
            metric: 'aum_growth'
        );

        $this->assertEquals(
            'aum_growth',
            $data['summary']['metric']
        );

        $this->assertEquals(
            88.88,
            $data['table_rows'][0]['metric_value']
        );
    }

    public function test_it_uses_custom_range()
    {
        $security = $this->createSecurity('CHPY');

        SecurityMetric::factory()->create([

            'security_id' => $security->id,

            'performance_range_type_id' => PerformanceRangeType::ONE_YEAR,

            'price_change_percentage' => 44.44,

        ]);

        $data = $this->service->getData(
            range: '1y'
        );

        $this->assertEquals(
            '1y',
            $data['summary']['range']
        );

        $this->assertEquals(
            44.44,
            $data['table_rows'][0]['metric_value']
        );
    }

    public function test_it_uses_custom_sort_direction()
    {
        $strong = $this->createSecurity('CHPY');

        $weak = $this->createSecurity('AMDY');

        SecurityMetric::factory()->create([

            'security_id' => $strong->id,

            'performance_range_type_id' => PerformanceRangeType::NINETY_DAY,

            'price_change_percentage' => 25,

        ]);

        SecurityMetric::factory()->create([

            'security_id' => $weak->id,

            'performance_range_type_id' => PerformanceRangeType::NINETY_DAY,

            'price_change_percentage' => 5,

        ]);

        $data = $this->service->getData(
            sortDirection: 'asc'
        );

        $this->assertEquals(
            'AMDY',
            $data['table_rows'][0]['symbol']
        );
    }

    public function test_it_respects_limit()
    {
        foreach (range(1, 10) as $index) {

            $security = $this->createSecurity(
                "SEC{$index}"
            );

            SecurityMetric::factory()->create([

                'security_id' => $security->id,

                'performance_range_type_id' => PerformanceRangeType::NINETY_DAY,

                'price_change_percentage' => $index,

            ]);
        }

        $data = $this->service->getData(
            limit: 5
        );

        $this->assertCount(
            5,
            $data['table_rows']
        );
    }

    public function test_it_returns_metric_options()
    {
        $data = $this->service->getData();

        $this->assertIsArray(
            $data['options']['metrics']
        );

        $this->assertGreaterThan(
            0,
            count(
                $data['options']['metrics']
            )
        );
    }

    public function test_it_returns_range_options()
    {
        $data = $this->service->getData();

        $this->assertIsArray(
            $data['options']['ranges']
        );

        $this->assertGreaterThan(
            0,
            count(
                $data['options']['ranges']
            )
        );
    }

    public function test_it_throws_exception_for_invalid_metric()
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        $this->service->getData(
            metric: 'invalid_metric'
        );
    }

    public function test_it_throws_exception_for_invalid_range()
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        $this->service->getData(
            range: 'invalid_range'
        );
    }

    public function test_it_throws_exception_for_invalid_sort_direction()
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        $this->service->getData(
            sortDirection: 'sideways'
        );
    }

    public function test_it_clamps_limit_to_maximum()
    {
        foreach (range(1, 150) as $index) {

            $security = $this->createSecurity(
                "SEC{$index}"
            );

            SecurityMetric::factory()->create([

                'security_id' => $security->id,

                'performance_range_type_id' => PerformanceRangeType::NINETY_DAY,

                'price_change_percentage' => $index,

            ]);
        }

        $data = $this->service->getData(
            limit: 999
        );

        $this->assertCount(
            100,
            $data['table_rows']
        );
    }

    public function test_it_clamps_limit_to_minimum()
    {
        $security = $this->createSecurity('CHPY');

        SecurityMetric::factory()->create([

            'security_id' => $security->id,

            'performance_range_type_id' => PerformanceRangeType::NINETY_DAY,

            'price_change_percentage' => 12,

        ]);

        $data = $this->service->getData(
            limit: 0
        );

        $this->assertCount(
            1,
            $data['table_rows']
        );
    }

    private function createSecurity(
        string $symbol
    ): Security {

        return Security::factory()->create([

            'symbol' => $symbol,

        ]);
    }
}

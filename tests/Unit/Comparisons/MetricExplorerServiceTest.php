<?php

namespace Tests\Unit\Comparisons;

use App\Models\Etf;
use App\Models\EtfMetric;
use App\Models\PerformanceRangeType;
use App\Queries\Comparisons\Metrics\RankEtfsByMetricQuery;
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

        DB::table('etf_metrics')->truncate();
        DB::table('etfs')->truncate();

        $this->service =
            new MetricExplorerService(

                new RankEtfsByMetricQuery

            );
    }

    protected function tearDown(): void
    {
        DB::table('etf_metrics')->truncate();
        DB::table('etfs')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_metric_explorer_payload()
    {
        $etf = $this->createEtf('CHPY');

        EtfMetric::factory()->create([

            'etf_id' => $etf->id,

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

            $etf = $this->createEtf(
                "ETF{$index}"
            );

            EtfMetric::factory()->create([

                'etf_id' => $etf->id,

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
        $etf = $this->createEtf('CHPY');

        EtfMetric::factory()->create([

            'etf_id' => $etf->id,

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
        $etf = $this->createEtf('CHPY');

        EtfMetric::factory()->create([

            'etf_id' => $etf->id,

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
        $strong = $this->createEtf('CHPY');

        $weak = $this->createEtf('AMDY');

        EtfMetric::factory()->create([

            'etf_id' => $strong->id,

            'performance_range_type_id' => PerformanceRangeType::NINETY_DAY,

            'price_change_percentage' => 25,

        ]);

        EtfMetric::factory()->create([

            'etf_id' => $weak->id,

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

            $etf = $this->createEtf(
                "ETF{$index}"
            );

            EtfMetric::factory()->create([

                'etf_id' => $etf->id,

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

            $etf = $this->createEtf(
                "ETF{$index}"
            );

            EtfMetric::factory()->create([

                'etf_id' => $etf->id,

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
        $etf = $this->createEtf('CHPY');

        EtfMetric::factory()->create([

            'etf_id' => $etf->id,

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

    private function createEtf(
        string $symbol
    ): Etf {

        return Etf::factory()->create([

            'symbol' => $symbol,

            'fund_name' => "{$symbol} Test ETF",

        ]);
    }
}

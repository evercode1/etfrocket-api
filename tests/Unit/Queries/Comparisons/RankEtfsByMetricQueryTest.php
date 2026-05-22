<?php

namespace Tests\Unit\Queries\Comparisons;

use App\Models\Etf;
use App\Models\EtfMetric;
use App\Models\PerformanceRangeType;
use App\Queries\Comparisons\Metrics\RankEtfsByMetricQuery;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RankEtfsByMetricQueryTest extends TestCase
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

    public function test_it_ranks_etfs_by_price_growth_descending()
    {
        $chpy = $this->createEtf('CHPY');

        $amdy = $this->createEtf('AMDY');

        EtfMetric::factory()->create([

            'etf_id' => $chpy->id,

            'performance_range_type_id' =>
            PerformanceRangeType::NINETY_DAY,

            'price_change_percentage' => 22.50,

        ]);

        EtfMetric::factory()->create([

            'etf_id' => $amdy->id,

            'performance_range_type_id' =>
            PerformanceRangeType::NINETY_DAY,

            'price_change_percentage' => 10.25,

        ]);

        $rows = (new RankEtfsByMetricQuery())->getData(

            metric: 'price_growth',

            range: '90d',

            metricConfig: [

                'label' => 'Price Growth',

                'metric_column' =>
                'price_change_percentage',

            ],

            sortDirection: 'desc',

            limit: 100,

        );

        $this->assertCount(
            2,
            $rows
        );

        $this->assertEquals(
            'CHPY',
            $rows[0]['symbol']
        );

        $this->assertEquals(
            22.50,
            $rows[0]['metric_value']
        );

        $this->assertEquals(
            1,
            $rows[0]['rank']
        );

        $this->assertEquals(
            'AMDY',
            $rows[1]['symbol']
        );

        $this->assertEquals(
            2,
            $rows[1]['rank']
        );
    }

    public function test_it_ranks_etfs_ascending()
    {
        $chpy = $this->createEtf('CHPY');

        $amdy = $this->createEtf('AMDY');

        EtfMetric::factory()->create([

            'etf_id' => $chpy->id,

            'performance_range_type_id' =>
            PerformanceRangeType::NINETY_DAY,

            'nav_erosion_percentage' => -15,

        ]);

        EtfMetric::factory()->create([

            'etf_id' => $amdy->id,

            'performance_range_type_id' =>
            PerformanceRangeType::NINETY_DAY,

            'nav_erosion_percentage' => -5,

        ]);

        $rows = (new RankEtfsByMetricQuery())->getData(

            metric: 'nav_stability',

            range: '90d',

            metricConfig: [

                'label' => 'NAV Stability',

                'metric_column' =>
                'nav_erosion_percentage',

            ],

            sortDirection: 'asc',

            limit: 100,

        );

        $this->assertEquals(
            'CHPY',
            $rows[0]['symbol']
        );

        $this->assertEquals(
            -15,
            $rows[0]['metric_value']
        );
    }

    public function test_it_respects_limit()
    {
        foreach (range(1, 5) as $index) {

            $etf = $this->createEtf(
                "ETF{$index}"
            );

            EtfMetric::factory()->create([

                'etf_id' => $etf->id,

                'performance_range_type_id' =>
                PerformanceRangeType::NINETY_DAY,

                'price_change_percentage' =>
                $index,

            ]);
        }

        $rows = (new RankEtfsByMetricQuery())->getData(

            metric: 'price_growth',

            range: '90d',

            metricConfig: [

                'label' => 'Price Growth',

                'metric_column' =>
                'price_change_percentage',

            ],

            sortDirection: 'desc',

            limit: 3,

        );

        $this->assertCount(
            3,
            $rows
        );
    }

    public function test_it_uses_correct_range_type()
    {
        $etf = $this->createEtf('CHPY');

        EtfMetric::factory()->create([

            'etf_id' => $etf->id,

            'performance_range_type_id' =>
            PerformanceRangeType::ONE_YEAR,

            'price_change_percentage' => 88.88,

        ]);

        $rows = (new RankEtfsByMetricQuery())->getData(

            metric: 'price_growth',

            range: '1y',

            metricConfig: [

                'label' => 'Price Growth',

                'metric_column' =>
                'price_change_percentage',

            ],

            sortDirection: 'desc',

            limit: 100,

        );

        $this->assertCount(
            1,
            $rows
        );

        $this->assertEquals(
            88.88,
            $rows[0]['metric_value']
        );
    }

    public function test_it_excludes_null_metric_values()
    {
        $chpy = $this->createEtf('CHPY');

        $amdy = $this->createEtf('AMDY');

        EtfMetric::factory()->create([

            'etf_id' => $chpy->id,

            'performance_range_type_id' =>
            PerformanceRangeType::NINETY_DAY,

            'price_change_percentage' => null,

        ]);

        EtfMetric::factory()->create([

            'etf_id' => $amdy->id,

            'performance_range_type_id' =>
            PerformanceRangeType::NINETY_DAY,

            'price_change_percentage' => 11.50,

        ]);

        $rows = (new RankEtfsByMetricQuery())->getData(

            metric: 'price_growth',

            range: '90d',

            metricConfig: [

                'label' => 'Price Growth',

                'metric_column' =>
                'price_change_percentage',

            ],

            sortDirection: 'desc',

            limit: 100,

        );

        $this->assertCount(
            1,
            $rows
        );

        $this->assertEquals(
            'AMDY',
            $rows[0]['symbol']
        );
    }

    public function test_it_resolves_nav_health_stable()
    {
        $etf = $this->createEtf('CHPY');

        EtfMetric::factory()->create([

            'etf_id' => $etf->id,

            'performance_range_type_id' =>
            PerformanceRangeType::NINETY_DAY,

            'nav_erosion_percentage' => 2,

            'price_change_percentage' => 5,

        ]);

        $rows = (new RankEtfsByMetricQuery())->getData(

            metric: 'price_growth',

            range: '90d',

            metricConfig: [

                'label' => 'Price Growth',

                'metric_column' =>
                'price_change_percentage',

            ],

            sortDirection: 'desc',

            limit: 100,

        );

        $this->assertEquals(
            'Stable',
            $rows[0]['nav_health']
        );
    }

    public function test_it_resolves_nav_health_watch()
    {
        $etf = $this->createEtf('CHPY');

        EtfMetric::factory()->create([

            'etf_id' => $etf->id,

            'performance_range_type_id' =>
            PerformanceRangeType::NINETY_DAY,

            'nav_erosion_percentage' => -15,

            'price_change_percentage' => 5,

        ]);

        $rows = (new RankEtfsByMetricQuery())->getData(

            metric: 'price_growth',

            range: '90d',

            metricConfig: [

                'label' => 'Price Growth',

                'metric_column' =>
                'price_change_percentage',

            ],

            sortDirection: 'desc',

            limit: 100,

        );

        $this->assertEquals(
            'Watch',
            $rows[0]['nav_health']
        );
    }

    public function test_it_returns_empty_array_when_no_rows_exist()
    {
        $rows = (new RankEtfsByMetricQuery())->getData(

            metric: 'price_growth',

            range: '90d',

            metricConfig: [

                'label' => 'Price Growth',

                'metric_column' =>
                'price_change_percentage',

            ],

            sortDirection: 'desc',

            limit: 100,

        );

        $this->assertSame(
            [],
            $rows
        );
    }

    private function createEtf(
        string $symbol
    ): Etf {

        return Etf::factory()->create([

            'symbol' => $symbol,

            'fund_name' =>
            "{$symbol} Test ETF",

        ]);
    }
}

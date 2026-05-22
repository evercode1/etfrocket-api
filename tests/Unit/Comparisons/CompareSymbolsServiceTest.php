<?php

namespace Tests\Unit\Comparisons;

use App\Models\Etf;
use App\Models\EtfMetric;
use App\Models\EtfPriceHistory;
use App\Models\PerformanceRangeType;
use App\Services\Comparisons\CompareSymbolsService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompareSymbolsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etf_price_histories')->truncate();
        DB::table('etf_metrics')->truncate();
        DB::table('etfs')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('etf_price_histories')->truncate();
        DB::table('etf_metrics')->truncate();
        DB::table('etfs')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_comparison_data()
    {
        $etf = $this->createEtf('CHPY');

        EtfPriceHistory::factory()->create([

            'etf_id' => $etf->id,

            'close_price' => 55.12,

        ]);

        EtfMetric::factory()->create([

            'etf_id' => $etf->id,

            'performance_range_type_id' =>
            PerformanceRangeType::THIRTY_DAY,

            'aum_change_percentage' => 12.50,

            'nav_erosion_percentage' => 3.50,

            'price_change_percentage' => 5.25,

        ]);

        EtfMetric::factory()->create([

            'etf_id' => $etf->id,

            'performance_range_type_id' =>

            PerformanceRangeType::NINETY_DAY,

            'total_return_percentage' => 24.80,

            'aum_change_percentage' => 12.50,

            'nav_erosion_percentage' => 3.50,

            'price_change_percentage' => 5.25,

        ]);

        $data = (new CompareSymbolsService)->getData(

            symbols: ['CHPY']

        );

        $this->assertEquals(
            1,
            $data['summary']['compared_etfs_count']
        );

        $this->assertEquals(
            '90d',
            $data['summary']['selected_range']
        );

        $this->assertEquals(
            'price',
            $data['summary']['selected_metric']
        );

        $this->assertEquals(
            'CHPY',
            $data['table_rows'][0]['symbol']
        );

        $this->assertEquals(
            55.12,
            $data['table_rows'][0]['latest_price']
        );

        $this->assertEquals(
            12.50,
            $data['table_rows'][0]['aum_change_percentage']
        );

        $this->assertEquals(
            24.80,
            $data['table_rows'][0]['total_return_percentage']
        );

        $this->assertEquals(
            3.50,
            $data['table_rows'][0]['nav_erosion_percentage']
        );

        $this->assertEquals(
            5.25,
            $data['table_rows'][0]['price_change_percentage']
        );

        $this->assertEquals(
            55.12,
            $data['table_rows'][0]['chart_value']
        );

        $this->assertEquals(
            'Stable',
            $data['table_rows'][0]['nav_health']
        );
    }

    public function test_it_returns_invalid_symbols()
    {
        $etf = $this->createEtf('CHPY');

        $data = (new CompareSymbolsService)->getData(

            symbols: ['CHPY', 'FAKE']

        );

        $this->assertEquals(
            ['FAKE'],
            $data['invalid_symbols']
        );

        $this->assertEquals(
            1,
            count($data['table_rows'])
        );

        $this->assertEquals(
            'CHPY',
            $data['table_rows'][0]['symbol']
        );
    }

    public function test_it_uses_correct_range_type_for_5d()
    {
        $etf = $this->createEtf('CHPY');

        EtfMetric::factory()->create([

            'etf_id' => $etf->id,

            'performance_range_type_id' =>
            PerformanceRangeType::FIVE_DAY,

            'total_return_percentage' => 5.00,

        ]);

        $data = (new CompareSymbolsService)->getData(

            symbols: ['CHPY'],

            range: '5d'

        );

        $this->assertEquals(
            '5d',
            $data['summary']['selected_range']
        );

        $this->assertEquals(
            5.00,
            $data['table_rows'][0]['total_return_percentage']
        );
    }

    public function test_it_uses_correct_range_type_for_1y()
    {
        $etf = $this->createEtf('CHPY');

        EtfMetric::factory()->create([

            'etf_id' => $etf->id,

            'performance_range_type_id' =>
            PerformanceRangeType::ONE_YEAR,

            'total_return_percentage' => 44.44,

        ]);

        $data = (new CompareSymbolsService)->getData(

            symbols: ['CHPY'],

            range: '1y'

        );

        $this->assertEquals(
            '1y',
            $data['summary']['selected_range']
        );

        $this->assertEquals(
            44.44,
            $data['table_rows'][0]['total_return_percentage']
        );
    }

    public function test_it_uses_return_metric_for_chart_value()
    {
        $etf = $this->createEtf('CHPY');

        EtfMetric::factory()->create([

            'etf_id' => $etf->id,

            'performance_range_type_id' =>
            PerformanceRangeType::NINETY_DAY,

            'total_return_percentage' => 19.75,

        ]);

        $data = (new CompareSymbolsService)->getData(

            symbols: ['CHPY'],

            metric: 'return'

        );

        $this->assertEquals(
            'return',
            $data['summary']['selected_metric']
        );

        $this->assertEquals(
            19.75,
            $data['table_rows'][0]['chart_value']
        );
    }

    public function test_it_uses_aum_metric_for_chart_value()
    {
        $etf = $this->createEtf('CHPY');

        EtfMetric::factory()->create([

            'etf_id' => $etf->id,

            'performance_range_type_id' =>
            PerformanceRangeType::NINETY_DAY,

            'aum_change_percentage' => 8.80,

        ]);

        $data = (new CompareSymbolsService)->getData(

            symbols: ['CHPY'],

            metric: 'aum'

        );

        $this->assertEquals(
            8.80,
            $data['table_rows'][0]['chart_value']
        );
    }

    public function test_it_uses_nav_metric_for_chart_value()
    {
        $etf = $this->createEtf('CHPY');

        EtfMetric::factory()->create([

            'etf_id' => $etf->id,

            'performance_range_type_id' =>
            PerformanceRangeType::NINETY_DAY,

            'nav_erosion_percentage' => -12.50,

        ]);

        $data = (new CompareSymbolsService)->getData(

            symbols: ['CHPY'],

            metric: 'nav'

        );

        $this->assertEquals(
            -12.50,
            $data['table_rows'][0]['chart_value']
        );

        $this->assertEquals(
            'Watch',
            $data['table_rows'][0]['nav_health']
        );
    }

    public function test_it_defaults_to_price_metric()
    {
        $etf = $this->createEtf('CHPY');

        EtfPriceHistory::factory()->create([

            'etf_id' => $etf->id,

            'close_price' => 77.77,

        ]);

        $data = (new CompareSymbolsService)->getData(

            symbols: ['CHPY']

        );

        $this->assertEquals(
            77.77,
            $data['table_rows'][0]['chart_value']
        );
    }

    private function createEtf(string $symbol): Etf
    {
        return Etf::factory()->create([

            'symbol' => $symbol,

            'fund_name' => "{$symbol} Test ETF",

        ]);
    }
}

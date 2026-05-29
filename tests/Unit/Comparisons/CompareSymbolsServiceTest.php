<?php

namespace Tests\Unit\Comparisons;

use App\Models\PerformanceRangeType;
use App\Models\Security;
use App\Models\SecurityAumHistory;
use App\Models\SecurityDividendHistory;
use App\Models\SecurityMetric;
use App\Models\SecurityNavHistory;
use App\Models\SecurityPriceHistory;
use App\Queries\Comparisons\SymbolAumHistoryChartQuery;
use App\Queries\Comparisons\SymbolDividendHistoryChartQuery;
use App\Queries\Comparisons\SymbolNavHistoryChartQuery;
use App\Queries\Comparisons\SymbolPriceHistoryChartQuery;
use App\Services\Comparisons\CompareSymbolsService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompareSymbolsServiceTest extends TestCase
{
    private CompareSymbolsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_price_histories')->truncate();
        DB::table('security_dividend_histories')->truncate();
        DB::table('security_nav_histories')->truncate();
        DB::table('security_aum_histories')->truncate();
        DB::table('security_metrics')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();

        $this->service = new CompareSymbolsService(

            new SymbolPriceHistoryChartQuery,

            new SymbolDividendHistoryChartQuery,

            new SymbolNavHistoryChartQuery,

            new SymbolAumHistoryChartQuery

        );
    }

    protected function tearDown(): void
    {
        DB::table('security_price_histories')->truncate();
        DB::table('security_dividend_histories')->truncate();
        DB::table('security_nav_histories')->truncate();
        DB::table('security_aum_histories')->truncate();
        DB::table('security_metrics')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        parent::tearDown();
    }

    public function test_it_returns_comparison_data()
    {
        $security = $this->createSecurity('CHPY');

        SecurityPriceHistory::factory()->create([

            'security_id' => $security->id,

            'price_date' => now(),

            'close_price' => 55.12,

        ]);

        SecurityMetric::factory()->create([

            'security_id' => $security->id,

            'performance_range_type_id' => PerformanceRangeType::NINETY_DAY,

            'total_return_percentage' => 24.80,

            'aum_change_percentage' => 12.50,

            'nav_erosion_percentage' => 3.50,

            'price_change_percentage' => 5.25,

        ]);

        $data = $this->service->getData(

            symbols: ['CHPY']

        );

        $this->assertEquals(
            1,
            $data['summary']['compared_securities_count']
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
        $this->createSecurity('CHPY');

        $data = $this->service->getData(

            symbols: ['CHPY', 'FAKE']

        );

        $this->assertEquals(
            ['FAKE'],
            $data['invalid_symbols']
        );

        $this->assertCount(
            1,
            $data['table_rows']
        );

        $this->assertEquals(
            'CHPY',
            $data['table_rows'][0]['symbol']
        );
    }

    public function test_it_uses_correct_range_type_for_5d()
    {
        $security = $this->createSecurity('CHPY');

        SecurityMetric::factory()->create([

            'security_id' => $security->id,

            'performance_range_type_id' => PerformanceRangeType::FIVE_DAY,

            'total_return_percentage' => 5.00,

        ]);

        $data = $this->service->getData(

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
        $security = $this->createSecurity('CHPY');

        SecurityMetric::factory()->create([

            'security_id' => $security->id,

            'performance_range_type_id' => PerformanceRangeType::ONE_YEAR,

            'total_return_percentage' => 44.44,

        ]);

        $data = $this->service->getData(

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
        $security = $this->createSecurity('CHPY');

        SecurityMetric::factory()->create([

            'security_id' => $security->id,

            'performance_range_type_id' => PerformanceRangeType::NINETY_DAY,

            'total_return_percentage' => 19.75,

        ]);

        $data = $this->service->getData(

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
        $security = $this->createSecurity('CHPY');

        SecurityMetric::factory()->create([

            'security_id' => $security->id,

            'performance_range_type_id' => PerformanceRangeType::NINETY_DAY,

            'aum_change_percentage' => 8.80,

        ]);

        $data = $this->service->getData(

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
        $security = $this->createSecurity('CHPY');

        SecurityMetric::factory()->create([

            'security_id' => $security->id,

            'performance_range_type_id' => PerformanceRangeType::NINETY_DAY,

            'nav_erosion_percentage' => -12.50,

        ]);

        $data = $this->service->getData(

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
        $security = $this->createSecurity('CHPY');

        SecurityPriceHistory::factory()->create([

            'security_id' => $security->id,

            'price_date' => now(),

            'close_price' => 77.77,

        ]);

        $data = $this->service->getData(

            symbols: ['CHPY']

        );

        $this->assertEquals(
            77.77,
            $data['table_rows'][0]['chart_value']
        );
    }

    public function test_it_generates_price_chart_rows()
    {
        $security = $this->createSecurity('CHPY');

        SecurityPriceHistory::factory()->create([

            'security_id' => $security->id,

            'price_date' => now(),

            'close_price' => 60,

        ]);

        $data = $this->service->getData(

            symbols: ['CHPY'],

            metric: 'price'

        );

        $this->assertCount(
            1,
            $data['chart_rows']
        );

        $this->assertEquals(
            60,
            $data['chart_rows'][0]['CHPY']
        );
    }

    public function test_it_generates_income_chart_rows()
    {
        $security = $this->createSecurity('CHPY');

        SecurityDividendHistory::factory()->create([

            'security_id' => $security->id,

            'ex_dividend_date' => now(),

            'dividend_amount' => 1.50,

        ]);

        $data = $this->service->getData(

            symbols: ['CHPY'],

            metric: 'income'

        );

        $this->assertCount(
            1,
            $data['chart_rows']
        );

        $this->assertEquals(
            1.50,
            $data['chart_rows'][0]['CHPY']
        );
    }

    public function test_it_generates_nav_chart_rows()
    {
        $security = $this->createSecurity('CHPY');

        SecurityNavHistory::factory()->create([

            'security_id' => $security->id,

            'nav_date' => now(),

            'nav_per_share' => 48.25,

        ]);

        $data = $this->service->getData(

            symbols: ['CHPY'],

            metric: 'nav'

        );

        $this->assertCount(
            1,
            $data['chart_rows']
        );

        $this->assertEquals(
            48.25,
            $data['chart_rows'][0]['CHPY']
        );
    }

    public function test_it_generates_aum_chart_rows()
    {
        $security = $this->createSecurity('CHPY');

        SecurityAumHistory::factory()->create([

            'security_id' => $security->id,

            'aum_date' => now(),

            'assets_under_management' => 100000000,

        ]);

        $data = $this->service->getData(

            symbols: ['CHPY'],

            metric: 'aum'

        );

        $this->assertCount(
            1,
            $data['chart_rows']
        );

        $this->assertEquals(
            100000000,
            $data['chart_rows'][0]['CHPY']
        );
    }

    private function createSecurity(string $symbol): Security
    {
        return Security::factory()->create([

            'symbol' => $symbol,

        ]);
    }
}

<?php

namespace Tests\Unit\BackTesting;

use App\Models\Etf;
use App\Models\EtfDividendHistory;
use App\Models\EtfPriceHistory;
use App\Queries\BackTesting\GetBackTestDividendHistoryQuery;
use App\Queries\BackTesting\GetBackTestPriceHistoryQuery;
use App\Services\BackTesting\BackTestingService;
use App\Services\BackTesting\GenerateBackTestAnalyticsService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BackTestingServiceTest extends TestCase
{
    private BackTestingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etf_price_histories')->truncate();
        DB::table('etf_dividend_histories')->truncate();
        DB::table('etfs')->truncate();

        $this->service =
            new BackTestingService(

                new GetBackTestPriceHistoryQuery(),

                new GetBackTestDividendHistoryQuery(),

                new GenerateBackTestAnalyticsService(),

            );
    }

    protected function tearDown(): void
    {
        DB::table('etf_price_histories')->truncate();
        DB::table('etf_dividend_histories')->truncate();
        DB::table('etfs')->truncate();

        parent::tearDown();
    }

    public function test_it_generates_chart_rows()
    {
        $etf = $this->createEtf('CHPY');

        EtfPriceHistory::factory()->create([

            'etf_id' => $etf->id,

            'price_date' => '2024-01-01',

            'close_price' => 100,

        ]);

        EtfPriceHistory::factory()->create([

            'etf_id' => $etf->id,

            'price_date' => '2024-02-01',

            'close_price' => 120,

        ]);

        $data = $this->service->getData(

            etfId: $etf->id,

            startDate: '2024-01-01',

            endDate: '2024-12-31',

            initialInvestment: 10000,

        );

        $this->assertCount(
            2,
            $data['chart_rows']
        );

        $this->assertEquals(
            '2024-01-01',
            $data['chart_rows'][0]['date']
        );

        $this->assertEquals(
            10000,
            $data['chart_rows'][0]['portfolio_value']
        );
    }

    public function test_it_calculates_final_value()
    {
        $etf = $this->createEtf('CHPY');

        EtfPriceHistory::factory()->create([

            'etf_id' => $etf->id,

            'price_date' => '2024-01-01',

            'close_price' => 100,

        ]);

        EtfPriceHistory::factory()->create([

            'etf_id' => $etf->id,

            'price_date' => '2024-02-01',

            'close_price' => 200,

        ]);

        $data = $this->service->getData(

            etfId: $etf->id,

            startDate: '2024-01-01',

            endDate: '2024-12-31',

            initialInvestment: 10000,

        );

        $this->assertEquals(
            20000,
            $data['summary']['final_value']
        );
    }

    public function test_it_applies_monthly_contributions()
    {
        $etf = $this->createEtf('CHPY');

        EtfPriceHistory::factory()->create([

            'etf_id' => $etf->id,

            'price_date' => '2024-01-01',

            'close_price' => 100,

        ]);

        EtfPriceHistory::factory()->create([

            'etf_id' => $etf->id,

            'price_date' => '2024-02-01',

            'close_price' => 100,

        ]);

        $data = $this->service->getData(

            etfId: $etf->id,

            startDate: '2024-01-01',

            endDate: '2024-12-31',

            initialInvestment: 10000,

            monthlyContribution: 1000,

        );

        $this->assertEquals(
            12000,
            $data['summary']['final_value']
        );

        $this->assertEquals(
            12000,
            $data['summary']['total_contributions']
        );
    }

    public function test_it_reinvests_dividends_when_drip_enabled()
    {
        $etf = $this->createEtf('CHPY');

        EtfPriceHistory::factory()->create([

            'etf_id' => $etf->id,

            'price_date' => '2024-01-01',

            'close_price' => 100,

        ]);

        EtfPriceHistory::factory()->create([

            'etf_id' => $etf->id,

            'price_date' => '2024-02-01',

            'close_price' => 100,

        ]);

        EtfDividendHistory::factory()->create([

            'etf_id' => $etf->id,

            'ex_dividend_date' => '2024-02-01',

            'dividend_amount' => 1,

        ]);

        $data = $this->service->getData(

            etfId: $etf->id,

            startDate: '2024-01-01',

            endDate: '2024-12-31',

            initialInvestment: 10000,

            dripPercentage: 100,

        );

        $this->assertGreaterThan(
            100,
            $data['summary']['ending_shares']
        );

        $this->assertEquals(
            100,
            $data['summary']['total_dividends']
        );
    }

    public function test_it_supports_partial_drip()
    {
        $etf = $this->createEtf('CHPY');

        EtfPriceHistory::factory()->create([

            'etf_id' => $etf->id,

            'price_date' => '2024-01-01',

            'close_price' => 100,

        ]);

        EtfPriceHistory::factory()->create([

            'etf_id' => $etf->id,

            'price_date' => '2024-02-01',

            'close_price' => 100,

        ]);

        EtfDividendHistory::factory()->create([

            'etf_id' => $etf->id,

            'ex_dividend_date' => '2024-02-01',

            'dividend_amount' => 1,

        ]);

        $data = $this->service->getData(

            etfId: $etf->id,

            startDate: '2024-01-01',

            endDate: '2024-12-31',

            initialInvestment: 10000,

            dripPercentage: 50,

        );

        $this->assertGreaterThan(
            100,
            $data['summary']['ending_shares']
        );

        $this->assertLessThan(
            101,
            $data['summary']['ending_shares']
        );
    }

    public function test_it_returns_empty_payload_when_no_prices_exist()
    {
        $etf = $this->createEtf('CHPY');

        $data = $this->service->getData(

            etfId: $etf->id,

            startDate: '2024-01-01',

            endDate: '2024-12-31',

            initialInvestment: 10000,

        );

        $this->assertSame(
            [],
            $data['chart_rows']
        );

        $this->assertEquals(
            0,
            $data['summary']['final_value']
        );

        $this->assertEquals(
            0,
            $data['analytics']['cagr']
        );

        $this->assertEquals(
            0,
            $data['analytics']['max_drawdown']
        );
    }

    public function test_it_tracks_total_dividends()
    {
        $etf = $this->createEtf('CHPY');

        EtfPriceHistory::factory()->create([

            'etf_id' => $etf->id,

            'price_date' => '2024-01-01',

            'close_price' => 100,

        ]);

        EtfPriceHistory::factory()->create([

            'etf_id' => $etf->id,

            'price_date' => '2024-02-01',

            'close_price' => 100,

        ]);

        EtfDividendHistory::factory()->create([

            'etf_id' => $etf->id,

            'ex_dividend_date' => '2024-02-01',

            'dividend_amount' => 2,

        ]);

        $data = $this->service->getData(

            etfId: $etf->id,

            startDate: '2024-01-01',

            endDate: '2024-12-31',

            initialInvestment: 10000,

        );

        $this->assertEquals(
            200,
            $data['summary']['total_dividends']
        );
    }

    public function test_it_returns_analytics_payload()
    {
        $etf = $this->createEtf('CHPY');

        EtfPriceHistory::factory()->create([

            'etf_id' => $etf->id,

            'price_date' => '2024-01-01',

            'close_price' => 100,

        ]);

        EtfPriceHistory::factory()->create([

            'etf_id' => $etf->id,

            'price_date' => '2025-01-01',

            'close_price' => 150,

        ]);

        $data = $this->service->getData(

            etfId: $etf->id,

            startDate: '2024-01-01',

            endDate: '2025-12-31',

            initialInvestment: 10000,

        );

        $this->assertArrayHasKey(
            'analytics',
            $data
        );

        $this->assertArrayHasKey(
            'cagr',
            $data['analytics']
        );

        $this->assertArrayHasKey(
            'max_drawdown',
            $data['analytics']
        );

        $this->assertArrayHasKey(
            'total_return_percentage',
            $data['analytics']
        );

        $this->assertGreaterThan(
            0,
            $data['analytics']['cagr']
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

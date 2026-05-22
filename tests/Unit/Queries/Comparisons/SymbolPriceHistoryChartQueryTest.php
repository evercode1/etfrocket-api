<?php

namespace Tests\Unit\Queries\Comparisons;

use App\Models\Etf;
use App\Models\EtfPriceHistory;
use App\Queries\Comparisons\SymbolPriceHistoryChartQuery;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SymbolPriceHistoryChartQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_chart_rows_for_single_symbol()
    {
        $etf = $this->createEtf('CHPY');

        EtfPriceHistory::factory()->create([

            'etf_id' => $etf->id,

            'price_date' => '2026-05-01',

            'close_price' => 50,

        ]);

        EtfPriceHistory::factory()->create([

            'etf_id' => $etf->id,

            'price_date' => '2026-05-02',

            'close_price' => 55,

        ]);

        $data = (new SymbolPriceHistoryChartQuery)->getData(

            etfIds: [$etf->id],

            startDate: '2026-05-01'

        );

        $this->assertCount(
            2,
            $data
        );

        $this->assertEquals(
            '2026-05-01',
            $data[0]['date']
        );

        $this->assertEquals(
            50,
            $data[0]['CHPY']
        );

        $this->assertEquals(
            '2026-05-02',
            $data[1]['date']
        );

        $this->assertEquals(
            55,
            $data[1]['CHPY']
        );
    }

    public function test_it_returns_chart_rows_for_multiple_symbols()
    {
        $chpy = $this->createEtf('CHPY');

        $amdy = $this->createEtf('AMDY');

        EtfPriceHistory::factory()->create([

            'etf_id' => $chpy->id,

            'price_date' => '2026-05-01',

            'close_price' => 70,

        ]);

        EtfPriceHistory::factory()->create([

            'etf_id' => $amdy->id,

            'price_date' => '2026-05-01',

            'close_price' => 40,

        ]);

        $data = (new SymbolPriceHistoryChartQuery)->getData(

            etfIds: [

                $chpy->id,

                $amdy->id,

            ],

            startDate: '2026-05-01'

        );

        $this->assertCount(
            1,
            $data
        );

        $this->assertEquals(
            '2026-05-01',
            $data[0]['date']
        );

        $this->assertEquals(
            70,
            $data[0]['CHPY']
        );

        $this->assertEquals(
            40,
            $data[0]['AMDY']
        );
    }

    public function test_it_filters_out_records_before_start_date()
    {
        $etf = $this->createEtf('CHPY');

        EtfPriceHistory::factory()->create([

            'etf_id' => $etf->id,

            'price_date' => '2026-04-01',

            'close_price' => 30,

        ]);

        EtfPriceHistory::factory()->create([

            'etf_id' => $etf->id,

            'price_date' => '2026-05-01',

            'close_price' => 50,

        ]);

        $data = (new SymbolPriceHistoryChartQuery)->getData(

            etfIds: [$etf->id],

            startDate: '2026-05-01'

        );

        $this->assertCount(
            1,
            $data
        );

        $this->assertEquals(
            '2026-05-01',
            $data[0]['date']
        );

        $this->assertEquals(
            50,
            $data[0]['CHPY']
        );
    }

    public function test_it_returns_empty_array_when_no_matching_records_exist()
    {
        $etf = $this->createEtf('CHPY');

        $data = (new SymbolPriceHistoryChartQuery)->getData(

            etfIds: [$etf->id],

            startDate: '2026-05-01'

        );

        $this->assertSame(
            [],
            $data
        );
    }

    public function test_it_orders_chart_rows_by_date()
    {
        $etf = $this->createEtf('CHPY');

        EtfPriceHistory::factory()->create([

            'etf_id' => $etf->id,

            'price_date' => '2026-05-03',

            'close_price' => 60,

        ]);

        EtfPriceHistory::factory()->create([

            'etf_id' => $etf->id,

            'price_date' => '2026-05-01',

            'close_price' => 40,

        ]);

        EtfPriceHistory::factory()->create([

            'etf_id' => $etf->id,

            'price_date' => '2026-05-02',

            'close_price' => 50,

        ]);

        $data = (new SymbolPriceHistoryChartQuery)->getData(

            etfIds: [$etf->id],

            startDate: '2026-05-01'

        );

        $this->assertEquals(
            '2026-05-01',
            $data[0]['date']
        );

        $this->assertEquals(
            '2026-05-02',
            $data[1]['date']
        );

        $this->assertEquals(
            '2026-05-03',
            $data[2]['date']
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

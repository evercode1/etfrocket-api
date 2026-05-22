<?php

namespace Tests\Unit\Queries\Comparisons;

use App\Models\Etf;
use App\Models\EtfDividendHistory;
use App\Queries\Comparisons\SymbolDividendHistoryChartQuery;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SymbolDividendHistoryChartQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etf_dividend_histories')->truncate();
        DB::table('etfs')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('etf_dividend_histories')->truncate();
        DB::table('etfs')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_chart_rows_for_single_symbol()
    {
        $etf = $this->createEtf('CHPY');

        EtfDividendHistory::factory()->create([

            'etf_id' => $etf->id,

            'ex_dividend_date' => '2026-05-01',

            'dividend_amount' => 1.25,

        ]);

        EtfDividendHistory::factory()->create([

            'etf_id' => $etf->id,

            'ex_dividend_date' => '2026-05-15',

            'dividend_amount' => 1.50,

        ]);

        $data = (new SymbolDividendHistoryChartQuery)->getData(

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
            1.25,
            $data[0]['CHPY']
        );

        $this->assertEquals(
            '2026-05-15',
            $data[1]['date']
        );

        $this->assertEquals(
            1.50,
            $data[1]['CHPY']
        );
    }

    public function test_it_returns_chart_rows_for_multiple_symbols()
    {
        $chpy = $this->createEtf('CHPY');

        $amdy = $this->createEtf('AMDY');

        EtfDividendHistory::factory()->create([

            'etf_id' => $chpy->id,

            'ex_dividend_date' => '2026-05-01',

            'dividend_amount' => 1.25,

        ]);

        EtfDividendHistory::factory()->create([

            'etf_id' => $amdy->id,

            'ex_dividend_date' => '2026-05-01',

            'dividend_amount' => 0.75,

        ]);

        $data = (new SymbolDividendHistoryChartQuery)->getData(

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
            1.25,
            $data[0]['CHPY']
        );

        $this->assertEquals(
            0.75,
            $data[0]['AMDY']
        );
    }

    public function test_it_filters_out_records_before_start_date()
    {
        $etf = $this->createEtf('CHPY');

        EtfDividendHistory::factory()->create([

            'etf_id' => $etf->id,

            'ex_dividend_date' => '2026-04-01',

            'dividend_amount' => 0.50,

        ]);

        EtfDividendHistory::factory()->create([

            'etf_id' => $etf->id,

            'ex_dividend_date' => '2026-05-01',

            'dividend_amount' => 1.25,

        ]);

        $data = (new SymbolDividendHistoryChartQuery)->getData(

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
            1.25,
            $data[0]['CHPY']
        );
    }

    public function test_it_returns_empty_array_when_no_matching_records_exist()
    {
        $etf = $this->createEtf('CHPY');

        $data = (new SymbolDividendHistoryChartQuery)->getData(

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

        EtfDividendHistory::factory()->create([

            'etf_id' => $etf->id,

            'ex_dividend_date' => '2026-05-15',

            'dividend_amount' => 1.50,

        ]);

        EtfDividendHistory::factory()->create([

            'etf_id' => $etf->id,

            'ex_dividend_date' => '2026-05-01',

            'dividend_amount' => 1.00,

        ]);

        EtfDividendHistory::factory()->create([

            'etf_id' => $etf->id,

            'ex_dividend_date' => '2026-05-10',

            'dividend_amount' => 1.25,

        ]);

        $data = (new SymbolDividendHistoryChartQuery)->getData(

            etfIds: [$etf->id],

            startDate: '2026-05-01'

        );

        $this->assertEquals(
            '2026-05-01',
            $data[0]['date']
        );

        $this->assertEquals(
            '2026-05-10',
            $data[1]['date']
        );

        $this->assertEquals(
            '2026-05-15',
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

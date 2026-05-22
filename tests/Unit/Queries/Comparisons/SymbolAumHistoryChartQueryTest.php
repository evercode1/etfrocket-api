<?php

namespace Tests\Unit\Queries\Comparisons;

use App\Models\Etf;
use App\Models\EtfAumHistory;
use App\Queries\Comparisons\SymbolAumHistoryChartQuery;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SymbolAumHistoryChartQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etf_aum_histories')->truncate();
        DB::table('etfs')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('etf_aum_histories')->truncate();
        DB::table('etfs')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_chart_rows_for_single_symbol()
    {
        $etf = $this->createEtf('CHPY');

        EtfAumHistory::factory()->create([

            'etf_id' => $etf->id,

            'aum_date' => '2026-05-01',

            'assets_under_management' => 100000000,

        ]);

        EtfAumHistory::factory()->create([

            'etf_id' => $etf->id,

            'aum_date' => '2026-05-15',

            'assets_under_management' => 120000000,

        ]);

        $data = (new SymbolAumHistoryChartQuery)->getData(

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
            100000000,
            $data[0]['CHPY']
        );

        $this->assertEquals(
            '2026-05-15',
            $data[1]['date']
        );

        $this->assertEquals(
            120000000,
            $data[1]['CHPY']
        );
    }

    public function test_it_returns_chart_rows_for_multiple_symbols()
    {
        $chpy = $this->createEtf('CHPY');

        $amdy = $this->createEtf('AMDY');

        EtfAumHistory::factory()->create([

            'etf_id' => $chpy->id,

            'aum_date' => '2026-05-01',

            'assets_under_management' => 100000000,

        ]);

        EtfAumHistory::factory()->create([

            'etf_id' => $amdy->id,

            'aum_date' => '2026-05-01',

            'assets_under_management' => 80000000,

        ]);

        $data = (new SymbolAumHistoryChartQuery)->getData(

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
            100000000,
            $data[0]['CHPY']
        );

        $this->assertEquals(
            80000000,
            $data[0]['AMDY']
        );
    }

    public function test_it_filters_out_records_before_start_date()
    {
        $etf = $this->createEtf('CHPY');

        EtfAumHistory::factory()->create([

            'etf_id' => $etf->id,

            'aum_date' => '2026-04-01',

            'assets_under_management' => 90000000,

        ]);

        EtfAumHistory::factory()->create([

            'etf_id' => $etf->id,

            'aum_date' => '2026-05-01',

            'assets_under_management' => 100000000,

        ]);

        $data = (new SymbolAumHistoryChartQuery)->getData(

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
            100000000,
            $data[0]['CHPY']
        );
    }

    public function test_it_returns_empty_array_when_no_matching_records_exist()
    {
        $etf = $this->createEtf('CHPY');

        $data = (new SymbolAumHistoryChartQuery)->getData(

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

        EtfAumHistory::factory()->create([

            'etf_id' => $etf->id,

            'aum_date' => '2026-05-15',

            'assets_under_management' => 120000000,

        ]);

        EtfAumHistory::factory()->create([

            'etf_id' => $etf->id,

            'aum_date' => '2026-05-01',

            'assets_under_management' => 100000000,

        ]);

        EtfAumHistory::factory()->create([

            'etf_id' => $etf->id,

            'aum_date' => '2026-05-10',

            'assets_under_management' => 110000000,

        ]);

        $data = (new SymbolAumHistoryChartQuery)->getData(

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

<?php

namespace Tests\Unit\Queries\BackTesting;

use App\Models\Etf;
use App\Models\EtfDividendHistory;
use App\Queries\BackTesting\GetBackTestDividendHistoryQuery;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GetBackTestDividendHistoryQueryTest extends TestCase
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

    public function test_it_returns_dividend_history_in_date_order()
    {
        $etf = $this->createEtf('CHPY');

        EtfDividendHistory::factory()->create([

            'etf_id' => $etf->id,

            'ex_dividend_date' => '2024-01-03',

            'dividend_amount' => 0.42,

        ]);

        EtfDividendHistory::factory()->create([

            'etf_id' => $etf->id,

            'ex_dividend_date' => '2024-01-01',

            'dividend_amount' => 0.35,

        ]);

        EtfDividendHistory::factory()->create([

            'etf_id' => $etf->id,

            'ex_dividend_date' => '2024-01-02',

            'dividend_amount' => 0.40,

        ]);

        $rows = (new GetBackTestDividendHistoryQuery())

            ->getData(

                etfId: $etf->id,

                startDate: '2024-01-01',

                endDate: '2024-01-31',

            );

        $this->assertCount(
            3,
            $rows
        );

        $this->assertEquals(
            '2024-01-01',
            $rows[0]['date']
        );

        $this->assertEquals(
            0.35,
            $rows[0]['dividend']
        );

        $this->assertEquals(
            '2024-01-02',
            $rows[1]['date']
        );

        $this->assertEquals(
            '2024-01-03',
            $rows[2]['date']
        );
    }

    public function test_it_filters_by_date_range()
    {
        $etf = $this->createEtf('CHPY');

        EtfDividendHistory::factory()->create([

            'etf_id' => $etf->id,

            'ex_dividend_date' => '2024-01-01',

            'dividend_amount' => 0.25,

        ]);

        EtfDividendHistory::factory()->create([

            'etf_id' => $etf->id,

            'ex_dividend_date' => '2024-02-01',

            'dividend_amount' => 0.55,

        ]);

        $rows = (new GetBackTestDividendHistoryQuery())

            ->getData(

                etfId: $etf->id,

                startDate: '2024-02-01',

                endDate: '2024-02-28',

            );

        $this->assertCount(
            1,
            $rows
        );

        $this->assertEquals(
            '2024-02-01',
            $rows[0]['date']
        );

        $this->assertEquals(
            0.55,
            $rows[0]['dividend']
        );
    }

    public function test_it_only_returns_rows_for_requested_etf()
    {
        $chpy = $this->createEtf('CHPY');

        $amdy = $this->createEtf('AMDY');

        EtfDividendHistory::factory()->create([

            'etf_id' => $chpy->id,

            'ex_dividend_date' => '2024-01-01',

            'dividend_amount' => 0.50,

        ]);

        EtfDividendHistory::factory()->create([

            'etf_id' => $amdy->id,

            'ex_dividend_date' => '2024-01-01',

            'dividend_amount' => 1.25,

        ]);

        $rows = (new GetBackTestDividendHistoryQuery())

            ->getData(

                etfId: $chpy->id,

                startDate: '2024-01-01',

                endDate: '2024-01-31',

            );

        $this->assertCount(
            1,
            $rows
        );

        $this->assertEquals(
            0.50,
            $rows[0]['dividend']
        );
    }

    public function test_it_returns_empty_array_when_no_rows_exist()
    {
        $etf = $this->createEtf('CHPY');

        $rows = (new GetBackTestDividendHistoryQuery())

            ->getData(

                etfId: $etf->id,

                startDate: '2024-01-01',

                endDate: '2024-01-31',

            );

        $this->assertSame(
            [],
            $rows
        );
    }

    public function test_it_returns_float_dividends()
    {
        $etf = $this->createEtf('CHPY');

        EtfDividendHistory::factory()->create([

            'etf_id' => $etf->id,

            'ex_dividend_date' => '2024-01-01',

            'dividend_amount' => '0.4475',

        ]);

        $rows = (new GetBackTestDividendHistoryQuery())

            ->getData(

                etfId: $etf->id,

                startDate: '2024-01-01',

                endDate: '2024-01-31',

            );

        $this->assertIsFloat(
            $rows[0]['dividend']
        );

        $this->assertEquals(
            0.4475,
            $rows[0]['dividend']
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

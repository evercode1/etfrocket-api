<?php

namespace Tests\Unit\Queries\Comparisons;

use App\Models\Security;
use App\Models\SecurityDividendHistory;
use App\Queries\Comparisons\SymbolDividendHistoryChartQuery;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SymbolDividendHistoryChartQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_dividend_histories')->truncate();
        DB::table('securities')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('security_dividend_histories')->truncate();
        DB::table('securities')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_chart_rows_for_single_symbol()
    {
        $security = $this->createSecurity('CHPY');

        SecurityDividendHistory::factory()->create([

            'security_id' => $security->id,

            'ex_dividend_date' => '2026-05-01',

            'dividend_amount' => 1.25,

        ]);

        SecurityDividendHistory::factory()->create([

            'security_id' => $security->id,

            'ex_dividend_date' => '2026-05-15',

            'dividend_amount' => 1.50,

        ]);

        $data = (new SymbolDividendHistoryChartQuery)->getData(

            securityIds: [$security->id],

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
        $chpy = $this->createSecurity('CHPY');

        $amdy = $this->createSecurity('AMDY');

        SecurityDividendHistory::factory()->create([

            'security_id' => $chpy->id,

            'ex_dividend_date' => '2026-05-01',

            'dividend_amount' => 1.25,

        ]);

        SecurityDividendHistory::factory()->create([

            'security_id' => $amdy->id,

            'ex_dividend_date' => '2026-05-01',

            'dividend_amount' => 0.75,

        ]);

        $data = (new SymbolDividendHistoryChartQuery)->getData(

            securityIds: [

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
        $security = $this->createSecurity('CHPY');

        SecurityDividendHistory::factory()->create([

            'security_id' => $security->id,

            'ex_dividend_date' => '2026-04-01',

            'dividend_amount' => 0.50,

        ]);

        SecurityDividendHistory::factory()->create([

            'security_id' => $security->id,

            'ex_dividend_date' => '2026-05-01',

            'dividend_amount' => 1.25,

        ]);

        $data = (new SymbolDividendHistoryChartQuery)->getData(

            securityIds: [$security->id],

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
        $security = $this->createSecurity('CHPY');

        $data = (new SymbolDividendHistoryChartQuery)->getData(

            securityIds: [$security->id],

            startDate: '2026-05-01'

        );

        $this->assertSame(
            [],
            $data
        );
    }

    public function test_it_orders_chart_rows_by_date()
    {
        $security = $this->createSecurity('CHPY');

        SecurityDividendHistory::factory()->create([

            'security_id' => $security->id,

            'ex_dividend_date' => '2026-05-15',

            'dividend_amount' => 1.50,

        ]);

        SecurityDividendHistory::factory()->create([

            'security_id' => $security->id,

            'ex_dividend_date' => '2026-05-01',

            'dividend_amount' => 1.00,

        ]);

        SecurityDividendHistory::factory()->create([

            'security_id' => $security->id,

            'ex_dividend_date' => '2026-05-10',

            'dividend_amount' => 1.25,

        ]);

        $data = (new SymbolDividendHistoryChartQuery)->getData(

            securityIds: [$security->id],

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

    private function createSecurity(string $symbol): Security
    {
        return Security::factory()->create([

            'symbol' => $symbol,

        ]);
    }
}

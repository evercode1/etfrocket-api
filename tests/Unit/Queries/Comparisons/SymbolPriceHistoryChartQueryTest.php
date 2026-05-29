<?php

namespace Tests\Unit\Queries\Comparisons;

use App\Models\Security;
use App\Models\SecurityPriceHistory;
use App\Queries\Comparisons\SymbolPriceHistoryChartQuery;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SymbolPriceHistoryChartQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_price_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('security_price_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_chart_rows_for_single_symbol()
    {
        $security = $this->createSecurity('CHPY');

        SecurityPriceHistory::factory()->create([

            'security_id' => $security->id,

            'price_date' => '2026-05-01',

            'close_price' => 50,

        ]);

        SecurityPriceHistory::factory()->create([

            'security_id' => $security->id,

            'price_date' => '2026-05-02',

            'close_price' => 55,

        ]);

        $data = (new SymbolPriceHistoryChartQuery)->getData(

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
        $chpy = $this->createSecurity('CHPY');

        $amdy = $this->createSecurity('AMDY');

        SecurityPriceHistory::factory()->create([

            'security_id' => $chpy->id,

            'price_date' => '2026-05-01',

            'close_price' => 70,

        ]);

        SecurityPriceHistory::factory()->create([

            'security_id' => $amdy->id,

            'price_date' => '2026-05-01',

            'close_price' => 40,

        ]);

        $data = (new SymbolPriceHistoryChartQuery)->getData(

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
        $security = $this->createSecurity('CHPY');

        SecurityPriceHistory::factory()->create([

            'security_id' => $security->id,

            'price_date' => '2026-04-01',

            'close_price' => 30,

        ]);

        SecurityPriceHistory::factory()->create([

            'security_id' => $security->id,

            'price_date' => '2026-05-01',

            'close_price' => 50,

        ]);

        $data = (new SymbolPriceHistoryChartQuery)->getData(

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
            50,
            $data[0]['CHPY']
        );
    }

    public function test_it_returns_empty_array_when_no_matching_records_exist()
    {
        $security = $this->createSecurity('CHPY');

        $data = (new SymbolPriceHistoryChartQuery)->getData(

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

        SecurityPriceHistory::factory()->create([

            'security_id' => $security->id,

            'price_date' => '2026-05-03',

            'close_price' => 60,

        ]);

        SecurityPriceHistory::factory()->create([

            'security_id' => $security->id,

            'price_date' => '2026-05-01',

            'close_price' => 40,

        ]);

        SecurityPriceHistory::factory()->create([

            'security_id' => $security->id,

            'price_date' => '2026-05-02',

            'close_price' => 50,

        ]);

        $data = (new SymbolPriceHistoryChartQuery)->getData(

            securityIds: [$security->id],

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

    private function createSecurity(string $symbol): Security
    {
        return Security::factory()->create([

            'symbol' => $symbol,

        ]);
    }
}

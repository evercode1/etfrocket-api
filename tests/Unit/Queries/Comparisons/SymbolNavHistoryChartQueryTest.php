<?php

namespace Tests\Unit\Queries\Comparisons;

use App\Models\Security;
use App\Models\SecurityNavHistory;
use App\Queries\Comparisons\SymbolNavHistoryChartQuery;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SymbolNavHistoryChartQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_nav_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('security_nav_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_chart_rows_for_single_symbol()
    {
        $security = $this->createSecurity('CHPY');

        SecurityNavHistory::factory()->create([

            'security_id' => $security->id,

            'nav_date' => '2026-05-01',

            'nav_per_share' => 48.25,

        ]);

        SecurityNavHistory::factory()->create([

            'security_id' => $security->id,

            'nav_date' => '2026-05-15',

            'nav_per_share' => 49.75,

        ]);

        $data = (new SymbolNavHistoryChartQuery)->getData(

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
            48.25,
            $data[0]['CHPY']
        );

        $this->assertEquals(
            '2026-05-15',
            $data[1]['date']
        );

        $this->assertEquals(
            49.75,
            $data[1]['CHPY']
        );
    }

    public function test_it_returns_chart_rows_for_multiple_symbols()
    {
        $chpy = $this->createSecurity('CHPY');

        $amdy = $this->createSecurity('AMDY');

        SecurityNavHistory::factory()->create([

            'security_id' => $chpy->id,

            'nav_date' => '2026-05-01',

            'nav_per_share' => 50.25,

        ]);

        SecurityNavHistory::factory()->create([

            'security_id' => $amdy->id,

            'nav_date' => '2026-05-01',

            'nav_per_share' => 42.75,

        ]);

        $data = (new SymbolNavHistoryChartQuery)->getData(

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
            50.25,
            $data[0]['CHPY']
        );

        $this->assertEquals(
            42.75,
            $data[0]['AMDY']
        );
    }

    public function test_it_filters_out_records_before_start_date()
    {
        $security = $this->createSecurity('CHPY');

        SecurityNavHistory::factory()->create([

            'security_id' => $security->id,

            'nav_date' => '2026-04-01',

            'nav_per_share' => 45.10,

        ]);

        SecurityNavHistory::factory()->create([

            'security_id' => $security->id,

            'nav_date' => '2026-05-01',

            'nav_per_share' => 48.25,

        ]);

        $data = (new SymbolNavHistoryChartQuery)->getData(

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
            48.25,
            $data[0]['CHPY']
        );
    }

    public function test_it_returns_empty_array_when_no_matching_records_exist()
    {
        $security = $this->createSecurity('CHPY');

        $data = (new SymbolNavHistoryChartQuery)->getData(

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

        SecurityNavHistory::factory()->create([

            'security_id' => $security->id,

            'nav_date' => '2026-05-15',

            'nav_per_share' => 51.75,

        ]);

        SecurityNavHistory::factory()->create([

            'security_id' => $security->id,

            'nav_date' => '2026-05-01',

            'nav_per_share' => 48.25,

        ]);

        SecurityNavHistory::factory()->create([

            'security_id' => $security->id,

            'nav_date' => '2026-05-10',

            'nav_per_share' => 50.00,

        ]);

        $data = (new SymbolNavHistoryChartQuery)->getData(

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

<?php

namespace Tests\Unit\Queries\Comparisons;

use App\Models\Security;
use App\Models\SecurityAumHistory;
use App\Queries\Comparisons\SymbolAumHistoryChartQuery;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SymbolAumHistoryChartQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_aum_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('security_aum_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_chart_rows_for_single_symbol()
    {
        $security = $this->createSecurity('CHPY');

        SecurityAumHistory::factory()->create([

            'security_id' => $security->id,

            'aum_date' => '2026-05-01',

            'assets_under_management' => 100000000,

        ]);

        SecurityAumHistory::factory()->create([

            'security_id' => $security->id,

            'aum_date' => '2026-05-15',

            'assets_under_management' => 120000000,

        ]);

        $data = (new SymbolAumHistoryChartQuery)->getData(

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
        $chpy = $this->createSecurity('CHPY');

        $amdy = $this->createSecurity('AMDY');

        SecurityAumHistory::factory()->create([

            'security_id' => $chpy->id,

            'aum_date' => '2026-05-01',

            'assets_under_management' => 100000000,

        ]);

        SecurityAumHistory::factory()->create([

            'security_id' => $amdy->id,

            'aum_date' => '2026-05-01',

            'assets_under_management' => 80000000,

        ]);

        $data = (new SymbolAumHistoryChartQuery)->getData(

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
        $security = $this->createSecurity('CHPY');

        SecurityAumHistory::factory()->create([

            'security_id' => $security->id,

            'aum_date' => '2026-04-01',

            'assets_under_management' => 90000000,

        ]);

        SecurityAumHistory::factory()->create([

            'security_id' => $security->id,

            'aum_date' => '2026-05-01',

            'assets_under_management' => 100000000,

        ]);

        $data = (new SymbolAumHistoryChartQuery)->getData(

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
            100000000,
            $data[0]['CHPY']
        );
    }

    public function test_it_returns_empty_array_when_no_matching_records_exist()
    {
        $security = $this->createSecurity('CHPY');

        $data = (new SymbolAumHistoryChartQuery)->getData(

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

        SecurityAumHistory::factory()->create([

            'security_id' => $security->id,

            'aum_date' => '2026-05-15',

            'assets_under_management' => 120000000,

        ]);

        SecurityAumHistory::factory()->create([

            'security_id' => $security->id,

            'aum_date' => '2026-05-01',

            'assets_under_management' => 100000000,

        ]);

        SecurityAumHistory::factory()->create([

            'security_id' => $security->id,

            'aum_date' => '2026-05-10',

            'assets_under_management' => 110000000,

        ]);

        $data = (new SymbolAumHistoryChartQuery)->getData(

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

<?php

namespace Tests\Unit\Queries\BackTesting;

use App\Models\Security;
use App\Models\SecurityDividendHistory;
use App\Queries\BackTesting\GetBackTestDividendHistoryQuery;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GetBackTestDividendHistoryQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_dividend_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('security_dividend_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_dividend_history_in_date_order()
    {
        $security = $this->createSecurity('CHPY');

        SecurityDividendHistory::factory()->create([

            'security_id' => $security->id,

            'ex_dividend_date' => '2024-01-03',

            'dividend_amount' => 0.42,

        ]);

        SecurityDividendHistory::factory()->create([

            'security_id' => $security->id,

            'ex_dividend_date' => '2024-01-01',

            'dividend_amount' => 0.35,

        ]);

        SecurityDividendHistory::factory()->create([

            'security_id' => $security->id,

            'ex_dividend_date' => '2024-01-02',

            'dividend_amount' => 0.40,

        ]);

        $rows = (new GetBackTestDividendHistoryQuery)

            ->getData(

                securityId: $security->id,

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
        $security = $this->createSecurity('CHPY');

        SecurityDividendHistory::factory()->create([

            'security_id' => $security->id,

            'ex_dividend_date' => '2024-01-01',

            'dividend_amount' => 0.25,

        ]);

        SecurityDividendHistory::factory()->create([

            'security_id' => $security->id,

            'ex_dividend_date' => '2024-02-01',

            'dividend_amount' => 0.55,

        ]);

        $rows = (new GetBackTestDividendHistoryQuery)

            ->getData(

                securityId: $security->id,

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

    public function test_it_only_returns_rows_for_requested_security()
    {
        $chpy = $this->createSecurity('CHPY');

        $amdy = $this->createSecurity('AMDY');

        SecurityDividendHistory::factory()->create([

            'security_id' => $chpy->id,

            'ex_dividend_date' => '2024-01-01',

            'dividend_amount' => 0.50,

        ]);

        SecurityDividendHistory::factory()->create([

            'security_id' => $amdy->id,

            'ex_dividend_date' => '2024-01-01',

            'dividend_amount' => 1.25,

        ]);

        $rows = (new GetBackTestDividendHistoryQuery)

            ->getData(

                securityId: $chpy->id,

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
        $security = $this->createSecurity('CHPY');

        $rows = (new GetBackTestDividendHistoryQuery)

            ->getData(

                securityId: $security->id,

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
        $security = $this->createSecurity('CHPY');

        SecurityDividendHistory::factory()->create([

            'security_id' => $security->id,

            'ex_dividend_date' => '2024-01-01',

            'dividend_amount' => '0.4475',

        ]);

        $rows = (new GetBackTestDividendHistoryQuery)

            ->getData(

                securityId: $security->id,

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

    private function createSecurity(
        string $symbol
    ): Security {

        return Security::factory()->create([

            'symbol' => $symbol,

        ]);
    }
}

<?php

namespace Tests\Unit\Queries\BackTesting;

use App\Models\Security;
use App\Models\SecurityPriceHistory;
use App\Queries\BackTesting\GetBackTestPriceHistoryQuery;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GetBackTestPriceHistoryQueryTest extends TestCase
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

    public function test_it_returns_price_history_in_date_order()
    {
        $security = $this->createSecurity('CHPY');

        SecurityPriceHistory::factory()->create([

            'security_id' => $security->id,

            'price_date' => '2024-01-03',

            'close_price' => 103.25,

        ]);

        SecurityPriceHistory::factory()->create([

            'security_id' => $security->id,

            'price_date' => '2024-01-01',

            'close_price' => 100.00,

        ]);

        SecurityPriceHistory::factory()->create([

            'security_id' => $security->id,

            'price_date' => '2024-01-02',

            'close_price' => 101.50,

        ]);

        $rows = (new GetBackTestPriceHistoryQuery)

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
            100.00,
            $rows[0]['price']
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

        SecurityPriceHistory::factory()->create([

            'security_id' => $security->id,

            'price_date' => '2024-01-01',

            'close_price' => 100,

        ]);

        SecurityPriceHistory::factory()->create([

            'security_id' => $security->id,

            'price_date' => '2024-02-01',

            'close_price' => 120,

        ]);

        $rows = (new GetBackTestPriceHistoryQuery)

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
            120,
            $rows[0]['price']
        );
    }

    public function test_it_only_returns_rows_for_requested_security()
    {
        $chpy = $this->createSecurity('CHPY');

        $amdy = $this->createSecurity('AMDY');

        SecurityPriceHistory::factory()->create([

            'security_id' => $chpy->id,

            'price_date' => '2024-01-01',

            'close_price' => 100,

        ]);

        SecurityPriceHistory::factory()->create([

            'security_id' => $amdy->id,

            'price_date' => '2024-01-01',

            'close_price' => 500,

        ]);

        $rows = (new GetBackTestPriceHistoryQuery)

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
            100,
            $rows[0]['price']
        );
    }

    public function test_it_returns_empty_array_when_no_rows_exist()
    {
        $security = $this->createSecurity('CHPY');

        $rows = (new GetBackTestPriceHistoryQuery)

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

    public function test_it_returns_float_prices()
    {
        $security = $this->createSecurity('CHPY');

        SecurityPriceHistory::factory()->create([

            'security_id' => $security->id,

            'price_date' => '2024-01-01',

            'close_price' => '105.4400',

        ]);

        $rows = (new GetBackTestPriceHistoryQuery)

            ->getData(

                securityId: $security->id,

                startDate: '2024-01-01',

                endDate: '2024-01-31',

            );

        $this->assertIsFloat(
            $rows[0]['price']
        );

        $this->assertEquals(
            105.44,
            $rows[0]['price']
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

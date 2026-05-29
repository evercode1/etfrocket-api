<?php

namespace Tests\Unit\Queries\Securities;

use App\Models\Security;
use App\Models\SecurityDetail;
use App\Models\Status;
use App\Queries\Securities\CompareSecuritiesQuery;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompareSecuritiesQueryTest extends TestCase
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

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_returns_comparison_series_for_selected_securities(): void
    {
        Carbon::setTestNow('2026-05-15');

        $schd = Security::create([
            'symbol' => 'SCHD',
            'status_id' => Status::ACTIVE,

        ]);

        SecurityDetail::factory()->create([
            'security_id' => $schd->id,
            'security_name' => 'SCHD_name',
        ]);

        $vym = Security::create([
            'symbol' => 'VYM',
            'status_id' => Status::ACTIVE,
        ]);

        SecurityDetail::factory()->create([
            'security_id' => $vym->id,
            'security_name' => 'VYM_name',
        ]);

        $this->createPriceHistory($schd->id, '2026-05-13', 78.12);
        $this->createPriceHistory($schd->id, '2026-05-14', 78.44);

        $this->createPriceHistory($vym->id, '2026-05-13', 119.25);
        $this->createPriceHistory($vym->id, '2026-05-14', 120.10);

        $results = (new CompareSecuritiesQuery)->getData([
            'metric' => 'price',
            'range' => '30d',
            'days' => 30,
            'security_ids' => [$schd->id, $vym->id],
            'table' => 'security_price_histories',
            'date_column' => 'price_date',
            'value_column' => 'close_price',
        ]);

        $this->assertSame('price', $results['metric']);
        $this->assertSame('30d', $results['range']);

        $this->assertCount(2, $results['series']);

        $this->assertSame($schd->id, $results['series'][0]['security_id']);
        $this->assertSame('SCHD', $results['series'][0]['symbol']);
        $this->assertSame('SCHD_name', $results['series'][0]['security_name']);

        $this->assertCount(2, $results['series'][0]['points']);
        $this->assertSame('2026-05-13', $results['series'][0]['points'][0]['date']);
        $this->assertEquals(78.12, $results['series'][0]['points'][0]['value']);
        $this->assertSame('2026-05-14', $results['series'][0]['points'][1]['date']);
        $this->assertEquals(78.44, $results['series'][0]['points'][1]['value']);

        $this->assertSame($vym->id, $results['series'][1]['security_id']);
        $this->assertSame('VYM', $results['series'][1]['symbol']);
        $this->assertSame('VYM_name', $results['series'][1]['security_name']);

        $this->assertCount(2, $results['series'][1]['points']);
        $this->assertSame('2026-05-13', $results['series'][1]['points'][0]['date']);
        $this->assertEquals(119.25, $results['series'][1]['points'][0]['value']);
        $this->assertSame('2026-05-14', $results['series'][1]['points'][1]['date']);
        $this->assertEquals(120.10, $results['series'][1]['points'][1]['value']);
    }

    public function test_it_filters_history_rows_by_date_range(): void
    {
        Carbon::setTestNow('2026-05-15');

        $security = Security::create([
            'symbol' => 'SCHD',
        ]);

        SecurityDetail::factory()->create([
            'security_id' => $security->id,
            'security_name' => 'SCHD_name',
        ]);

        $this->createPriceHistory($security->id, '2026-04-01', 75.00);
        $this->createPriceHistory($security->id, '2026-05-01', 77.00);
        $this->createPriceHistory($security->id, '2026-05-14', 78.00);

        $results = (new CompareSecuritiesQuery)->getData([
            'metric' => 'price',
            'range' => '30d',
            'days' => 30,
            'security_ids' => [$security->id],
            'table' => 'security_price_histories',
            'date_column' => 'price_date',
            'value_column' => 'close_price',
        ]);

        $this->assertCount(1, $results['series']);
        $this->assertCount(2, $results['series'][0]['points']);

        $this->assertSame('2026-05-01', $results['series'][0]['points'][0]['date']);
        $this->assertSame('2026-05-14', $results['series'][0]['points'][1]['date']);
    }

    public function test_it_orders_points_by_comparison_date(): void
    {
        Carbon::setTestNow('2026-05-15');

        $security = Security::create([
            'symbol' => 'SCHD',
        ]);

        SecurityDetail::factory()->create([
            'security_id' => $security->id,
            'security_name' => 'SCHD_name',
        ]);

        $this->createPriceHistory($security->id, '2026-05-14', 78.00);
        $this->createPriceHistory($security->id, '2026-05-12', 76.00);
        $this->createPriceHistory($security->id, '2026-05-13', 77.00);

        $results = (new CompareSecuritiesQuery)->getData([
            'metric' => 'price',
            'range' => '30d',
            'days' => 30,
            'security_ids' => [$security->id],
            'table' => 'security_price_histories',
            'date_column' => 'price_date',
            'value_column' => 'close_price',
        ]);

        $this->assertSame('2026-05-12', $results['series'][0]['points'][0]['date']);
        $this->assertSame('2026-05-13', $results['series'][0]['points'][1]['date']);
        $this->assertSame('2026-05-14', $results['series'][0]['points'][2]['date']);
    }

    public function test_it_preserves_requested_etf_order(): void
    {
        Carbon::setTestNow('2026-05-15');

        $firstCreated = Security::create(['symbol' => 'AAA']);

        $secondCreated = Security::create(['symbol' => 'BBB']);

        SecurityDetail::factory()->create([
            'security_id' => $firstCreated->id,
            'security_name' => 'AAA_name',
        ]);

        SecurityDetail::factory()->create([
            'security_id' => $secondCreated->id,
            'security_name' => 'BBB_name',
        ]);

        $this->createPriceHistory($firstCreated->id, '2026-05-14', 10.00);
        $this->createPriceHistory($secondCreated->id, '2026-05-14', 20.00);

        $results = (new CompareSecuritiesQuery)->getData([
            'metric' => 'price',
            'range' => '30d',
            'days' => 30,
            'security_ids' => [$secondCreated->id, $firstCreated->id],
            'table' => 'security_price_histories',
            'date_column' => 'price_date',
            'value_column' => 'close_price',
        ]);

        $this->assertSame('BBB', $results['series'][0]['symbol']);
        $this->assertSame('AAA', $results['series'][1]['symbol']);
    }

    public function test_it_skips_requested_security_ids_that_do_not_exist(): void
    {
        Carbon::setTestNow('2026-05-15');

        $security = Security::create([
            'symbol' => 'SCHD',
        ]);

        SecurityDetail::factory()->create([
            'security_id' => $security->id,
            'security_name' => 'SCHD_name',
        ]);

        $this->createPriceHistory($security->id, '2026-05-14', 78.00);

        $results = (new CompareSecuritiesQuery)->getData([
            'metric' => 'price',
            'range' => '30d',
            'days' => 30,
            'security_ids' => [$security->id, 999999],
            'table' => 'security_price_histories',
            'date_column' => 'price_date',
            'value_column' => 'close_price',
        ]);

        $this->assertCount(1, $results['series']);
        $this->assertSame('SCHD', $results['series'][0]['symbol']);
    }

    public function test_it_returns_empty_points_for_existing_security_with_no_history(): void
    {
        Carbon::setTestNow('2026-05-15');

        $security = Security::create([
            'symbol' => 'NOHIST',
        ]);

        SecurityDetail::factory()->create([
            'security_id' => $security->id,
            'security_name' => 'NOHIST_name',
        ]);

        $results = (new CompareSecuritiesQuery)->getData([
            'metric' => 'price',
            'range' => '30d',
            'days' => 30,
            'security_ids' => [$security->id],
            'table' => 'security_price_histories',
            'date_column' => 'price_date',
            'value_column' => 'close_price',
        ]);

        $this->assertCount(1, $results['series']);
        $this->assertSame('NOHIST', $results['series'][0]['symbol']);
        $this->assertSame([], $results['series'][0]['points']);
    }

    private function createPriceHistory(int $securityId, string $date, ?float $closePrice): void
    {
        DB::table('security_price_histories')->insert([
            'security_id' => $securityId,
            'price_date' => $date,
            'close_price' => $closePrice,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}

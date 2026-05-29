<?php

namespace Tests\Unit\Queries\Etfs;

use App\Models\Etf;
use App\Queries\Etfs\CompareEtfsQuery;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompareEtfsQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_returns_comparison_series_for_selected_etfs(): void
    {
        Carbon::setTestNow('2026-05-15');

        $schd = Etf::factory()->create([
            'symbol' => 'SCHD',
            'fund_name' => 'Schwab U.S. Dividend Equity ETF',
        ]);

        $vym = Etf::factory()->create([
            'symbol' => 'VYM',
            'fund_name' => 'Vanguard High Dividend Yield ETF',
        ]);

        $this->createPriceHistory($schd->id, '2026-05-13', 78.12);
        $this->createPriceHistory($schd->id, '2026-05-14', 78.44);

        $this->createPriceHistory($vym->id, '2026-05-13', 119.25);
        $this->createPriceHistory($vym->id, '2026-05-14', 120.10);

        $results = (new CompareEtfsQuery)->getData([
            'metric' => 'price',
            'range' => '30d',
            'days' => 30,
            'etf_ids' => [$schd->id, $vym->id],
            'table' => 'etf_price_histories',
            'date_column' => 'price_date',
            'value_column' => 'close_price',
        ]);

        $this->assertSame('price', $results['metric']);
        $this->assertSame('30d', $results['range']);

        $this->assertCount(2, $results['series']);

        $this->assertSame($schd->id, $results['series'][0]['etf_id']);
        $this->assertSame('SCHD', $results['series'][0]['symbol']);
        $this->assertSame('Schwab U.S. Dividend Equity ETF', $results['series'][0]['fund_name']);

        $this->assertCount(2, $results['series'][0]['points']);
        $this->assertSame('2026-05-13', $results['series'][0]['points'][0]['date']);
        $this->assertEquals(78.12, $results['series'][0]['points'][0]['value']);
        $this->assertSame('2026-05-14', $results['series'][0]['points'][1]['date']);
        $this->assertEquals(78.44, $results['series'][0]['points'][1]['value']);

        $this->assertSame($vym->id, $results['series'][1]['etf_id']);
        $this->assertSame('VYM', $results['series'][1]['symbol']);

        $this->assertCount(2, $results['series'][1]['points']);
        $this->assertSame('2026-05-13', $results['series'][1]['points'][0]['date']);
        $this->assertEquals(119.25, $results['series'][1]['points'][0]['value']);
        $this->assertSame('2026-05-14', $results['series'][1]['points'][1]['date']);
        $this->assertEquals(120.10, $results['series'][1]['points'][1]['value']);
    }

    public function test_it_filters_history_rows_by_date_range(): void
    {
        Carbon::setTestNow('2026-05-15');

        $etf = Etf::factory()->create([
            'symbol' => 'SCHD',
        ]);

        $this->createPriceHistory($etf->id, '2026-04-01', 75.00);
        $this->createPriceHistory($etf->id, '2026-05-01', 77.00);
        $this->createPriceHistory($etf->id, '2026-05-14', 78.00);

        $results = (new CompareEtfsQuery)->getData([
            'metric' => 'price',
            'range' => '30d',
            'days' => 30,
            'etf_ids' => [$etf->id],
            'table' => 'etf_price_histories',
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

        $etf = Etf::factory()->create([
            'symbol' => 'SCHD',
        ]);

        $this->createPriceHistory($etf->id, '2026-05-14', 78.00);
        $this->createPriceHistory($etf->id, '2026-05-12', 76.00);
        $this->createPriceHistory($etf->id, '2026-05-13', 77.00);

        $results = (new CompareEtfsQuery)->getData([
            'metric' => 'price',
            'range' => '30d',
            'days' => 30,
            'etf_ids' => [$etf->id],
            'table' => 'etf_price_histories',
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

        $firstCreated = Etf::factory()->create(['symbol' => 'AAA']);
        $secondCreated = Etf::factory()->create(['symbol' => 'BBB']);

        $this->createPriceHistory($firstCreated->id, '2026-05-14', 10.00);
        $this->createPriceHistory($secondCreated->id, '2026-05-14', 20.00);

        $results = (new CompareEtfsQuery)->getData([
            'metric' => 'price',
            'range' => '30d',
            'days' => 30,
            'etf_ids' => [$secondCreated->id, $firstCreated->id],
            'table' => 'etf_price_histories',
            'date_column' => 'price_date',
            'value_column' => 'close_price',
        ]);

        $this->assertSame('BBB', $results['series'][0]['symbol']);
        $this->assertSame('AAA', $results['series'][1]['symbol']);
    }

    public function test_it_skips_requested_etf_ids_that_do_not_exist(): void
    {
        Carbon::setTestNow('2026-05-15');

        $etf = Etf::factory()->create([
            'symbol' => 'SCHD',
        ]);

        $this->createPriceHistory($etf->id, '2026-05-14', 78.00);

        $results = (new CompareEtfsQuery)->getData([
            'metric' => 'price',
            'range' => '30d',
            'days' => 30,
            'etf_ids' => [$etf->id, 999999],
            'table' => 'etf_price_histories',
            'date_column' => 'price_date',
            'value_column' => 'close_price',
        ]);

        $this->assertCount(1, $results['series']);
        $this->assertSame('SCHD', $results['series'][0]['symbol']);
    }

    public function test_it_returns_empty_points_for_existing_etf_with_no_history(): void
    {
        Carbon::setTestNow('2026-05-15');

        $etf = Etf::factory()->create([
            'symbol' => 'NOHIST',
        ]);

        $results = (new CompareEtfsQuery)->getData([
            'metric' => 'price',
            'range' => '30d',
            'days' => 30,
            'etf_ids' => [$etf->id],
            'table' => 'etf_price_histories',
            'date_column' => 'price_date',
            'value_column' => 'close_price',
        ]);

        $this->assertCount(1, $results['series']);
        $this->assertSame('NOHIST', $results['series'][0]['symbol']);
        $this->assertSame([], $results['series'][0]['points']);
    }

    private function createPriceHistory(int $etfId, string $date, ?float $closePrice): void
    {
        DB::table('etf_price_histories')->insert([
            'etf_id' => $etfId,
            'price_date' => $date,
            'close_price' => $closePrice,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}

<?php

namespace Tests\Unit\Queries\Securities;

use App\Models\Security;
use App\Models\SecurityAumHistory;
use App\Models\SecurityDividendHistory;
use App\Models\SecurityNavHistory;
use App\Models\SecurityPriceHistory;
use App\Models\Status;
use App\Queries\Securities\SecurityChartQuery;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecurityChartQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        DB::table('security_aum_histories')->truncate();
        DB::table('security_dividend_histories')->truncate();
        DB::table('security_nav_histories')->truncate();
        DB::table('security_price_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
    }

    protected function tearDown(): void
    {
        Cache::flush();

        DB::table('security_aum_histories')->truncate();
        DB::table('security_dividend_histories')->truncate();
        DB::table('security_nav_histories')->truncate();
        DB::table('security_price_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();

        parent::tearDown();
    }

    public function test_it_merges_all_chart_data(): void
    {
        $security = Security::factory()->create([
            'symbol' => 'ABNY',
            'status_id' => Status::ACTIVE,
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2026-05-01',
            'close_price' => 40,
        ]);

        SecurityNavHistory::factory()->create([
            'security_id' => $security->id,
            'nav_date' => '2026-05-01',
            'nav_per_share' => 39,
        ]);

        SecurityAumHistory::factory()->create([
            'security_id' => $security->id,
            'aum_date' => '2026-05-01',
            'assets_under_management' => 50000000,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'ex_dividend_date' => '2026-05-01',
            'payment_date' => '2026-05-08',
            'dividend_amount' => 0.42,
            'data_source_id' => 1,
        ]);

        $results = app(
            SecurityChartQuery::class
        )->getData(
            $security->id,
            '2026-01-01'
        );

        $this->assertCount(
            1,
            $results
        );

        $this->assertSame(
            '2026-05-01',
            $results[0]['date']
        );

        $this->assertSame(
            40.0,
            (float) $results[0]['price']
        );

        $this->assertSame(
            39.0,
            (float) $results[0]['nav']
        );

        $this->assertSame(
            50000000.0,
            (float) $results[0]['aum']
        );

        $this->assertSame(
            0.42,
            (float) $results[0]['dividend']
        );
    }

    public function test_it_returns_null_for_missing_data_types(): void
    {
        $security = Security::factory()->create([
            'symbol' => 'ABNY',
            'status_id' => Status::ACTIVE,
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2026-05-01',
            'close_price' => 40,
        ]);

        $results = app(
            SecurityChartQuery::class
        )->getData(
            $security->id,
            '2026-01-01'
        );

        $this->assertCount(
            1,
            $results
        );

        $this->assertSame(
            40.0,
            (float) $results[0]['price']
        );

        $this->assertNull(
            $results[0]['nav']
        );

        $this->assertNull(
            $results[0]['aum']
        );

        $this->assertNull(
            $results[0]['dividend']
        );
    }

    public function test_it_sorts_rows_by_date(): void
    {
        $security = Security::factory()->create([
            'symbol' => 'ABNY',
            'status_id' => Status::ACTIVE,
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2026-06-01',
            'close_price' => 50,
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2026-05-01',
            'close_price' => 40,
        ]);

        $results = app(
            SecurityChartQuery::class
        )->getData(
            $security->id,
            '2026-01-01'
        );

        $this->assertSame(
            '2026-05-01',
            $results[0]['date']
        );

        $this->assertSame(
            '2026-06-01',
            $results[1]['date']
        );
    }
}

<?php

namespace Tests\Feature\Console;

use App\Models\Etf;
use App\Models\EtfMetric;
use App\Models\EtfAumHistory;
use App\Models\EtfNavHistory;
use App\Models\EtfPriceHistory;
use App\Models\PerformanceRangeType;
use Carbon\Carbon;
use Database\Seeders\EtfAumHistorySeeder;
use Database\Seeders\EtfDividendHistorySeeder;
use Database\Seeders\EtfNavHistorySeeder;
use Database\Seeders\EtfPriceHistorySeeder;
use Database\Seeders\EtfSeeder;
use Database\Seeders\PerformanceRangeTypeSeeder;
use Database\Seeders\StatusSeeder;
use Database\Seeders\IntervalSeeder;
use Database\Seeders\NotificationStatusSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CalculateEtfMetricsCommandSeededDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-05-01 12:00:00');

        DB::table('etf_metrics')->truncate();
        DB::table('etf_dividend_histories')->truncate();
        DB::table('etf_aum_histories')->truncate();
        DB::table('etf_nav_histories')->truncate();
        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();
        DB::table('statuses')->truncate();
        DB::table('performance_range_types')->truncate();
        DB::table('cron_logs')->truncate();
        DB::table('intervals')->truncate();
        DB::table('notification_statuses')->truncate();

        $this->seed(StatusSeeder::class);
        $this->seed(PerformanceRangeTypeSeeder::class);
        $this->seed(EtfSeeder::class);
        $this->seed(EtfPriceHistorySeeder::class);
        $this->seed(EtfNavHistorySeeder::class);
        $this->seed(EtfAumHistorySeeder::class);
        $this->seed(EtfDividendHistorySeeder::class);
        $this->seed(IntervalSeeder::class);
        $this->seed(NotificationStatusSeeder::class);
    }

    protected function tearDown(): void
    {
        DB::table('etf_metrics')->truncate();
        DB::table('etf_dividend_histories')->truncate();
        DB::table('etf_aum_histories')->truncate();
        DB::table('etf_nav_histories')->truncate();
        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();
        DB::table('statuses')->truncate();
        DB::table('performance_range_types')->truncate();
        DB::table('cron_logs')->truncate();
        DB::table('intervals')->truncate();
        DB::table('notification_statuses')->truncate();

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_calculates_metrics_from_seeded_historical_data()
    {
        $this->assertEquals(7, Etf::count());
        $this->assertEquals(217, DB::table('etf_price_histories')->count());
        $this->assertEquals(217, DB::table('etf_nav_histories')->count());
        $this->assertEquals(217, DB::table('etf_aum_histories')->count());
        $this->assertGreaterThan(0, DB::table('etf_dividend_histories')->count());

        $this->artisan('etfs:calculate-metrics')
            ->assertExitCode(0);

        $this->assertGreaterThan(0, EtfMetric::count());

        $chpy = Etf::where('symbol', 'CHPY')->firstOrFail();

        $metric = EtfMetric::where('etf_id', $chpy->id)
            ->where('performance_range_type_id', PerformanceRangeType::THIRTY_DAY)
            ->first();

        $this->assertNotNull($metric);

        $this->assertEquals('2026-04-01', $metric->start_date->toDateString());
        $this->assertEquals('2026-05-01', $metric->end_date->toDateString());

        $this->assertEquals(48.0600, (float) $metric->start_price);
        $this->assertEquals(47.6200, (float) $metric->end_price);
        $this->assertEquals(-0.4400, (float) $metric->price_change);
        $this->assertEquals(-0.9155, (float) $metric->price_change_percentage);

        $this->assertEquals(2.2775, (float) $metric->dividends_paid);
        $this->assertEquals(5, $metric->dividend_count);
        $this->assertEquals(0.4555, (float) $metric->average_dividend);

        $this->assertEquals(3.8233, (float) $metric->total_return_percentage);

        $this->assertEquals(47.9400, (float) $metric->start_nav);
        $this->assertEquals(47.6200, (float) $metric->end_nav);
        $this->assertEquals(-0.3200, (float) $metric->nav_change);
        $this->assertEquals(-0.6675, (float) $metric->nav_erosion_percentage);

        $this->assertEquals(590000000, (int) $metric->start_aum);
        $this->assertEquals(620000000, (int) $metric->end_aum);
        $this->assertEquals(30000000, (int) $metric->aum_change);
        $this->assertEquals(5.0847, (float) $metric->aum_change_percentage);
    }

    public function test_it_updates_seeded_metrics_instead_of_creating_duplicates()
    {
        $this->artisan('etfs:calculate-metrics')
            ->assertExitCode(0);

        $firstCount = EtfMetric::count();

        $this->assertGreaterThan(0, $firstCount);

        $this->artisan('etfs:calculate-metrics')
            ->assertExitCode(0);

        $this->assertEquals($firstCount, EtfMetric::count());
    }

    public function test_it_calculates_ninety_day_metric_when_older_history_exists()
    {
        $chpy = Etf::where('symbol', 'CHPY')->firstOrFail();

        EtfPriceHistory::factory()->create([
            'etf_id' => $chpy->id,
            'price_date' => '2026-01-31',
            'close_price' => 45.0000,
        ]);

        EtfNavHistory::factory()->create([
            'etf_id' => $chpy->id,
            'nav_date' => '2026-01-31',
            'nav_per_share' => 46.0000,
        ]);

        EtfAumHistory::factory()->create([
            'etf_id' => $chpy->id,
            'aum_date' => '2026-01-31',
            'assets_under_management' => 500000000,
        ]);

        $this->artisan('etfs:calculate-metrics --symbol=CHPY')
            ->assertExitCode(0);

        $metric = EtfMetric::where('etf_id', $chpy->id)
            ->where('performance_range_type_id', PerformanceRangeType::NINETY_DAY)
            ->first();

        $this->assertNotNull($metric);
        $this->assertEquals('2026-01-31', $metric->start_date->toDateString());
        $this->assertEquals('2026-05-01', $metric->end_date->toDateString());
        $this->assertEquals(45.0000, (float) $metric->start_price);
        $this->assertEquals(47.6200, (float) $metric->end_price);
        $this->assertEquals(5.8222, (float) $metric->price_change_percentage);
    }

    public function test_it_calculates_one_year_metric_when_older_history_exists()
    {
        $chpy = Etf::where('symbol', 'CHPY')->firstOrFail();

        EtfPriceHistory::factory()->create([
            'etf_id' => $chpy->id,
            'price_date' => '2025-05-01',
            'close_price' => 40.0000,
        ]);

        EtfNavHistory::factory()->create([
            'etf_id' => $chpy->id,
            'nav_date' => '2025-05-01',
            'nav_per_share' => 42.0000,
        ]);

        EtfAumHistory::factory()->create([
            'etf_id' => $chpy->id,
            'aum_date' => '2025-05-01',
            'assets_under_management' => 400000000,
        ]);

        $this->artisan('etfs:calculate-metrics --symbol=CHPY')
            ->assertExitCode(0);

        $metric = EtfMetric::where('etf_id', $chpy->id)
            ->where('performance_range_type_id', PerformanceRangeType::ONE_YEAR)
            ->first();

        $this->assertNotNull($metric);
        $this->assertEquals('2025-05-01', $metric->start_date->toDateString());
        $this->assertEquals('2026-05-01', $metric->end_date->toDateString());
        $this->assertEquals(40.0000, (float) $metric->start_price);
        $this->assertEquals(47.6200, (float) $metric->end_price);
        $this->assertEquals(19.0500, (float) $metric->price_change_percentage);
    }
}

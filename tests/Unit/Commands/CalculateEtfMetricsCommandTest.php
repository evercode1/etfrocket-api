<?php

namespace Tests\Unit\Commands;

use App\Models\DataSource;
use App\Models\DistributionFrequency;
use App\Models\Etf;
use App\Models\EtfIssuer;
use App\Models\EtfMetric;
use App\Models\EtfPriceHistory;
use App\Models\EtfStrategyType;
use App\Models\Status;
use Carbon\Carbon;
use Database\Seeders\DataSourceSeeder;
use Database\Seeders\DistributionFrequencySeeder;
use Database\Seeders\EtfIssuerSeeder;
use Database\Seeders\EtfStrategyTypeSeeder;
use Database\Seeders\IntervalSeeder;
use Database\Seeders\MetricDirectionSeeder;
use Database\Seeders\NotificationStatusSeeder;
use Database\Seeders\PerformanceRangeTypeSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CalculateEtfMetricsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-05-12 12:00:00'));

        DB::table('etf_metrics')->truncate();
        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();

        DB::table('data_sources')->truncate();
        DB::table('performance_range_types')->truncate();
        DB::table('metric_directions')->truncate();
        DB::table('distribution_frequencies')->truncate();
        DB::table('etf_strategy_types')->truncate();
        DB::table('etf_issuers')->truncate();
        DB::table('statuses')->truncate();
        DB::table('cron_logs')->truncate();
        DB::table('intervals')->truncate();
        DB::table('notification_statuses')->truncate();

        $this->seed(StatusSeeder::class);
        $this->seed(EtfIssuerSeeder::class);
        $this->seed(EtfStrategyTypeSeeder::class);
        $this->seed(DistributionFrequencySeeder::class);
        $this->seed(DataSourceSeeder::class);
        $this->seed(PerformanceRangeTypeSeeder::class);
        $this->seed(MetricDirectionSeeder::class);
        $this->seed(IntervalSeeder::class);
        $this->seed(NotificationStatusSeeder::class);
    }

    protected function tearDown(): void
    {
        DB::table('etf_metrics')->truncate();
        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();

        DB::table('data_sources')->truncate();
        DB::table('performance_range_types')->truncate();
        DB::table('metric_directions')->truncate();
        DB::table('distribution_frequencies')->truncate();
        DB::table('etf_strategy_types')->truncate();
        DB::table('etf_issuers')->truncate();
        DB::table('statuses')->truncate();
        DB::table('cron_logs')->truncate();
        DB::table('intervals')->truncate();
        DB::table('notification_statuses')->truncate();

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_calculates_metrics_for_all_active_etfs_and_all_range_types()
    {
        $activeEtfOne = $this->createEtf('AAA', Status::ACTIVE);
        $activeEtfTwo = $this->createEtf('BBB', Status::ACTIVE);
        $inactiveEtf = $this->createEtf('CCC', Status::INACTIVE);

        $this->createPriceHistory($activeEtfOne);
        $this->createPriceHistory($activeEtfTwo);
        $this->createPriceHistory($inactiveEtf);

        $this->artisan(

            'etfs:calculate-metrics'

        )->assertExitCode(0);

        $this->assertEquals(12, EtfMetric::count());

        $this->assertEquals(
            6,
            EtfMetric::where('etf_id', $activeEtfOne->id)->count()
        );

        $this->assertEquals(
            6,
            EtfMetric::where('etf_id', $activeEtfTwo->id)->count()
        );

        $this->assertEquals(
            0,
            EtfMetric::where('etf_id', $inactiveEtf->id)->count()
        );
    }

    public function test_it_calculates_metrics_for_single_symbol_when_symbol_option_is_used()
    {
        $targetEtf = $this->createEtf('CHPY', Status::ACTIVE);
        $otherEtf = $this->createEtf('AMDY', Status::ACTIVE);

        $this->createPriceHistory($targetEtf);
        $this->createPriceHistory($otherEtf);

        $this->artisan('etfs:calculate-metrics --symbol=CHPY')
            ->assertExitCode(0);

        $this->assertEquals(6, EtfMetric::where('etf_id', $targetEtf->id)->count());

        $this->assertEquals(0, EtfMetric::where('etf_id', $otherEtf->id)->count());
    }

    public function test_it_returns_success_when_no_active_etfs_are_found()
    {
        $this->createEtf('ZZZ', Status::INACTIVE);

        $this->artisan('etfs:calculate-metrics')
            ->assertExitCode(0);

        $this->assertEquals(0, EtfMetric::count());
    }

    private function createEtf(string $symbol, int $statusId): Etf
    {
        return Etf::create([
            'symbol' => $symbol,
            'fund_name' => $symbol.' Test ETF',
            'etf_issuer_id' => EtfIssuer::YIELDMAX,
            'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
            'distribution_frequency_id' => DistributionFrequency::WEEKLY,
            'status_id' => $statusId,
            'expense_ratio' => 0.99,
            'inception_date' => '2026-01-01',
            'source' => 'manual',
            'website_url' => 'https://example.com',
            'notes' => null,
        ]);
    }

    private function createPriceHistory(Etf $etf): void
    {
        EtfPriceHistory::create([
            'etf_id' => $etf->id,
            'price_date' => '2026-04-12',
            'close_price' => 10.0000,
            'volume' => 100000,
            'data_source_id' => DataSource::MANUAL_ENTRY,
            'retrieved_at' => now(),
        ]);

        EtfPriceHistory::create([
            'etf_id' => $etf->id,
            'price_date' => '2026-05-12',
            'close_price' => 12.0000,
            'volume' => 200000,
            'data_source_id' => DataSource::MANUAL_ENTRY,
            'retrieved_at' => now(),
        ]);
    }
}

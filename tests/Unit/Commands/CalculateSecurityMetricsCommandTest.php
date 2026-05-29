<?php

namespace Tests\Unit\Commands;

use App\Models\DataSource;
use App\Models\Security;
use App\Models\SecurityMetric;
use App\Models\SecurityPriceHistory;
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

class CalculateSecurityMetricsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-05-12 12:00:00'));

        DB::table('security_metrics')->truncate();
        DB::table('security_price_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
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
        DB::table('security_metrics')->truncate();
        DB::table('security_price_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
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
        $activeEtfOne = $this->createSecurity('AAA', Status::ACTIVE);
        $activeEtfTwo = $this->createSecurity('BBB', Status::ACTIVE);
        $inactiveEtf = $this->createSecurity('CCC', Status::INACTIVE);

        $this->createPriceHistory($activeEtfOne);
        $this->createPriceHistory($activeEtfTwo);
        $this->createPriceHistory($inactiveEtf);

        $this->artisan(

            'securities:calculate-metrics'

        )->assertExitCode(0);

        $this->assertEquals(12, SecurityMetric::count());

        $this->assertEquals(
            6,
            SecurityMetric::where('security_id', $activeEtfOne->id)->count()
        );

        $this->assertEquals(
            6,
            SecurityMetric::where('security_id', $activeEtfTwo->id)->count()
        );

        $this->assertEquals(
            0,
            SecurityMetric::where('security_id', $inactiveEtf->id)->count()
        );
    }

    public function test_it_calculates_metrics_for_single_symbol_when_symbol_option_is_used()
    {
        $targetEtf = $this->createSecurity('CHPY', Status::ACTIVE);
        $otherEtf = $this->createSecurity('AMDY', Status::ACTIVE);

        $this->createPriceHistory($targetEtf);
        $this->createPriceHistory($otherEtf);

        $this->artisan('securities:calculate-metrics --symbol=CHPY')
            ->assertExitCode(0);

        $this->assertEquals(6, SecurityMetric::where('security_id', $targetEtf->id)->count());

        $this->assertEquals(0, SecurityMetric::where('security_id', $otherEtf->id)->count());
    }

    public function test_it_returns_success_when_no_active_securities_are_found()
    {
        $this->createSecurity('ZZZ', Status::INACTIVE);

        $this->artisan('securities:calculate-metrics')
            ->assertExitCode(0);

        $this->assertEquals(0, SecurityMetric::count());
    }

    private function createSecurity(string $symbol, int $statusId): Security
    {
        return Security::create([
            'symbol' => $symbol,

            'status_id' => $statusId,

        ]);
    }

    private function createPriceHistory(Security $security): void
    {
        SecurityPriceHistory::create([
            'security_id' => $security->id,
            'price_date' => '2026-04-12',
            'close_price' => 10.0000,
            'volume' => 100000,
            'data_source_id' => DataSource::MANUAL_ENTRY,
            'retrieved_at' => now(),
        ]);

        SecurityPriceHistory::create([
            'security_id' => $security->id,
            'price_date' => '2026-05-12',
            'close_price' => 12.0000,
            'volume' => 200000,
            'data_source_id' => DataSource::MANUAL_ENTRY,
            'retrieved_at' => now(),
        ]);
    }
}

<?php

namespace Tests\Unit\Commands\Handlers;

use App\Models\DataSource;
use App\Models\ImportLog;
use App\Models\ImportType;
use App\Models\Security;
use App\Models\SecurityMetric;
use App\Models\SecurityPriceHistory;
use App\Models\Status;
use App\Services\Crons\Handlers\CalculateSecurityMetricsHandler;
use Carbon\Carbon;
use Database\Seeders\DataSourceSeeder;
use Database\Seeders\DistributionFrequencySeeder;
use Database\Seeders\EtfIssuerSeeder;
use Database\Seeders\EtfStrategyTypeSeeder;
use Database\Seeders\ImportTypeSeeder;
use Database\Seeders\MetricDirectionSeeder;
use Database\Seeders\PerformanceRangeTypeSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CalculateSecurityMetricsHandlerTest extends TestCase
{
    private CalculateSecurityMetricsHandler $handler;

    protected function setUp(): void
    {

        parent::setUp();

        Carbon::setTestNow(

            Carbon::parse(

                '2026-05-12 12:00:00'

            )

        );

        DB::table('import_logs')->truncate();
        DB::table('import_types')->truncate();
        DB::table('security_metrics')->truncate();
        DB::table('security_price_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('performance_range_types')->truncate();
        DB::table('metric_directions')->truncate();
        DB::table('distribution_frequencies')->truncate();
        DB::table('etf_strategy_types')->truncate();
        DB::table('etf_issuers')->truncate();
        DB::table('statuses')->truncate();
        DB::table('data_sources')->truncate();

        $this->seed([

            StatusSeeder::class,
            EtfIssuerSeeder::class,
            EtfStrategyTypeSeeder::class,
            DistributionFrequencySeeder::class,
            DataSourceSeeder::class,
            PerformanceRangeTypeSeeder::class,
            MetricDirectionSeeder::class,
            ImportTypeSeeder::class,

        ]);

        $this->handler =

            app(

                CalculateSecurityMetricsHandler::class

            );
    }

    protected function tearDown(): void
    {

        DB::table('import_logs')->truncate();
        DB::table('import_types')->truncate();
        DB::table('security_metrics')->truncate();
        DB::table('security_price_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('performance_range_types')->truncate();
        DB::table('metric_directions')->truncate();
        DB::table('distribution_frequencies')->truncate();
        DB::table('etf_strategy_types')->truncate();
        DB::table('etf_issuers')->truncate();
        DB::table('statuses')->truncate();
        DB::table('data_sources')->truncate();

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_calculates_metrics_for_all_active_securities()
    {

        $securityOne =

            $this->createSecurity(

                'AAA',

                Status::ACTIVE

            );

        $securityTwo =

            $this->createSecurity(

                'BBB',

                Status::ACTIVE

            );

        $this->createPriceHistory(

            $securityOne

        );

        $this->createPriceHistory(

            $securityTwo

        );

        $results =

            $this->handler
                ->handleCalculateSecurityMetrics([

                    'force' => true,

                ]);

        $this->assertEquals(

            1,

            $results['success']

        );

        $this->assertEquals(

            12,

            SecurityMetric::count()

        );

        $this->assertDatabaseHas(

            'import_logs',

            [

                'import_type_id' => ImportType::CALCULATE_SECURITY_METRICS,

                'status_id' => Status::COMPLETED,

            ]

        );
    }

    public function test_it_only_processes_requested_symbol()
    {

        $targetSecurity =

            $this->createSecurity(

                'CHPY',

                Status::ACTIVE

            );

        $otherSecurity =

            $this->createSecurity(

                'AMDY',

                Status::ACTIVE

            );

        $this->createPriceHistory(

            $targetSecurity

        );

        $this->createPriceHistory(

            $otherSecurity

        );

        $results =

            $this->handler
                ->handleCalculateSecurityMetrics([

                    'symbol' => 'CHPY',

                    'force' => true,

                ]);

        $this->assertEquals(

            1,

            $results['success']

        );

        $this->assertEquals(

            6,

            SecurityMetric::where(

                'security_id',

                $targetSecurity->id

            )->count()

        );

        $this->assertEquals(

            0,

            SecurityMetric::where(

                'security_id',

                $otherSecurity->id

            )->count()

        );
    }

    public function test_it_skips_inactive_securities()
    {

        $inactiveSecurity =

            $this->createSecurity(

                'ZZZ',

                Status::INACTIVE

            );

        $this->createPriceHistory(

            $inactiveSecurity

        );

        $results =

            $this->handler
                ->handleCalculateSecurityMetrics([

                    'force' => true,

                ]);

        $this->assertEquals(

            1,

            $results['success']

        );

        $this->assertEquals(

            0,

            SecurityMetric::count()

        );
    }

    public function test_it_returns_success_when_no_active_securities_exist()
    {

        $this->createSecurity(

            'NONE',

            Status::INACTIVE

        );

        $results =

            $this->handler
                ->handleCalculateSecurityMetrics([

                    'force' => true,

                ]);

        $this->assertEquals(

            1,

            $results['success']

        );

        $this->assertNull(

            $results['cron_fail_details']

        );
    }

    public function test_it_skips_when_no_fresh_price_data_exists()
    {

        SecurityMetric::create([

            'security_id' => 1,

            'performance_range_type_id' => 1,

            'end_date' => '2026-05-12',

            'calculated_at' => now(),

        ]);

        SecurityPriceHistory::create([

            'security_id' => 1,

            'price_date' => '2026-05-12',

            'close_price' => 12,

            'volume' => 1000,

            'data_source_id' => DataSource::MANUAL_ENTRY,

            'retrieved_at' => now(),

        ]);

        $results =

            $this->handler
                ->handleCalculateSecurityMetrics();

        $this->assertEquals(

            1,

            $results['success']

        );

        $this->assertDatabaseHas(

            'import_logs',

            [
                'import_type_id' => ImportType::CALCULATE_SECURITY_METRICS,

                'processing_notes' => 'Skipped security metric calculation. No fresh security price data detected.',

            ]

        );
    }

    public function test_force_flag_bypasses_freshness_check()
    {

        $security =

            $this->createSecurity(

                'FORCE',

                Status::ACTIVE

            );

        $this->createPriceHistory(

            $security

        );

        SecurityMetric::create([

            'security_id' => $security->id,

            'performance_range_type_id' => 1,

            'end_date' => '2026-05-12',

            'calculated_at' => now(),

        ]);

        $results =

            $this->handler
                ->handleCalculateSecurityMetrics([

                    'force' => true,

                ]);

        $this->assertEquals(

            1,

            $results['success']

        );

        $this->assertTrue(

            SecurityMetric::count() > 1

        );
    }

    private function createSecurity(

        string $symbol,

        int $statusId

    ): Security {

        return Security::create([

            'symbol' => $symbol,

            'status_id' => $statusId,

        ]);
    }

    private function createPriceHistory(

        Security $security

    ): void {

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

    public function test_it_logs_aggregated_import_metrics()
    {

        $securityOne =

            $this->createSecurity(

                'AAA',

                Status::ACTIVE

            );

        $securityTwo =

            $this->createSecurity(

                'BBB',

                Status::ACTIVE

            );

        $this->createPriceHistory(

            $securityOne

        );

        $this->createPriceHistory(

            $securityTwo

        );

        $results =

            $this->handler
                ->handleCalculateSecurityMetrics([

                    'force' => true,

                ]);

        $this->assertEquals(

            1,

            $results['success']

        );

        $log =

            ImportLog::latest()
                ->first();

        $this->assertNotNull(

            $log

        );

        /*

    |--------------------------------------------------------------------------

    | Runtime

    |--------------------------------------------------------------------------

    */

        $this->assertGreaterThanOrEqual(

            0,

            $log->run_time

        );

        /*

    |--------------------------------------------------------------------------

    | Processing Counts

    |--------------------------------------------------------------------------

    */

        // 2 Securities × 6 ranges

        $this->assertEquals(

            12,

            $log->rows_processed

        );

        $this->assertEquals(

            12,

            $log->records_updated

        );

        $this->assertEquals(

            0,

            $log->records_created

        );

        $this->assertEquals(

            0,

            $log->duplicate_rows

        );

        $this->assertEquals(

            0,

            $log->failure_count

        );

        /*

    |--------------------------------------------------------------------------

    | Processing Notes

    |--------------------------------------------------------------------------

    */

        $this->assertEquals(

            'Forced security metric recalculation executed successfully.',

            $log->processing_notes

        );

        /*

    |--------------------------------------------------------------------------

    | Failure Details

    |--------------------------------------------------------------------------

    */

        $this->assertNull(

            $log->import_fail_details

        );

        /*

    |--------------------------------------------------------------------------

    | Integrity

    |--------------------------------------------------------------------------

    */

        $this->assertEquals(

            1,

            $log->passed_data_integrity_check

        );
    }
}

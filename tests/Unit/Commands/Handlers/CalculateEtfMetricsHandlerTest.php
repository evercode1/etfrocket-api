<?php

namespace Tests\Unit\Commands\Handlers;

use App\Models\DataSource;
use App\Models\DistributionFrequency;
use App\Models\Etf;
use App\Models\EtfIssuer;
use App\Models\EtfMetric;
use App\Models\EtfPriceHistory;
use App\Models\EtfStrategyType;
use App\Models\ImportType;
use App\Models\Status;
use App\Models\ImportLog;
use App\Services\Crons\Handlers\CalculateEtfMetricsHandler;
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

class CalculateEtfMetricsHandlerTest extends TestCase

{

    private CalculateEtfMetricsHandler

        $handler;

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
        DB::table('etf_metrics')->truncate();
        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();
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

                CalculateEtfMetricsHandler::class

            );
    }

    protected function tearDown(): void

    {

        DB::table('import_logs')->truncate();
        DB::table('import_types')->truncate();
        DB::table('etf_metrics')->truncate();
        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();
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

    public function test_it_calculates_metrics_for_all_active_etfs()

    {

        $etfOne =

            $this->createEtf(

                'AAA',

                Status::ACTIVE

            );

        $etfTwo =

            $this->createEtf(

                'BBB',

                Status::ACTIVE

            );

        $this->createPriceHistory(

            $etfOne

        );

        $this->createPriceHistory(

            $etfTwo

        );

        $results =

            $this->handler

            ->handleCalculateEtfMetrics([

                'force' => true,

            ]);

        $this->assertEquals(

            1,

            $results['success']

        );

        $this->assertEquals(

            12,

            EtfMetric::count()

        );

        $this->assertDatabaseHas(

            'import_logs',

            [

                'import_type_id' =>

                ImportType::CALCULATE_ETF_METRICS,

                'status_id' =>

                Status::COMPLETED,

            ]

        );
    }

    public function test_it_only_processes_requested_symbol()

    {

        $targetEtf =

            $this->createEtf(

                'CHPY',

                Status::ACTIVE

            );

        $otherEtf =

            $this->createEtf(

                'AMDY',

                Status::ACTIVE

            );

        $this->createPriceHistory(

            $targetEtf

        );

        $this->createPriceHistory(

            $otherEtf

        );

        $results =

            $this->handler

            ->handleCalculateEtfMetrics([

                'symbol' => 'CHPY',

                'force' => true,

            ]);

        $this->assertEquals(

            1,

            $results['success']

        );

        $this->assertEquals(

            6,

            EtfMetric::where(

                'etf_id',

                $targetEtf->id

            )->count()

        );

        $this->assertEquals(

            0,

            EtfMetric::where(

                'etf_id',

                $otherEtf->id

            )->count()

        );
    }

    public function test_it_skips_inactive_etfs()

    {

        $inactiveEtf =

            $this->createEtf(

                'ZZZ',

                Status::INACTIVE

            );

        $this->createPriceHistory(

            $inactiveEtf

        );

        $results =

            $this->handler

            ->handleCalculateEtfMetrics([

                'force' => true,

            ]);

        $this->assertEquals(

            1,

            $results['success']

        );

        $this->assertEquals(

            0,

            EtfMetric::count()

        );
    }

    public function test_it_returns_success_when_no_active_etfs_exist()

    {

        $this->createEtf(

            'NONE',

            Status::INACTIVE

        );

        $results =

            $this->handler

            ->handleCalculateEtfMetrics([

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

        EtfMetric::create([

            'etf_id' => 1,

            'performance_range_type_id' => 1,

            'end_date' => '2026-05-12',

            'calculated_at' => now(),

        ]);

        EtfPriceHistory::create([

            'etf_id' => 1,

            'price_date' => '2026-05-12',

            'close_price' => 12,

            'volume' => 1000,

            'data_source_id' =>

            DataSource::MANUAL_ENTRY,

            'retrieved_at' => now(),

        ]);

        $results =

            $this->handler

            ->handleCalculateEtfMetrics();

        $this->assertEquals(

            1,

            $results['success']

        );

        $this->assertDatabaseHas(

            'import_logs',

            [

                'import_type_id' =>

                ImportType::CALCULATE_ETF_METRICS,

                'processing_notes' =>

                'Skipped ETF metric calculation. No fresh ETF price data detected.',

            ]

        );
    }

    public function test_force_flag_bypasses_freshness_check()

    {

        $etf =

            $this->createEtf(

                'FORCE',

                Status::ACTIVE

            );

        $this->createPriceHistory(

            $etf

        );

        EtfMetric::create([

            'etf_id' => $etf->id,

            'performance_range_type_id' => 1,

            'end_date' => '2026-05-12',

            'calculated_at' => now(),

        ]);

        $results =

            $this->handler

            ->handleCalculateEtfMetrics([

                'force' => true,

            ]);

        $this->assertEquals(

            1,

            $results['success']

        );

        $this->assertTrue(

            EtfMetric::count() > 1

        );
    }

    private function createEtf(

        string $symbol,

        int $statusId

    ): Etf {

        return Etf::create([

            'symbol' => $symbol,

            'fund_name' =>

            $symbol . ' Test ETF',

            'etf_issuer_id' =>

            EtfIssuer::YIELDMAX,

            'etf_strategy_type_id' =>

            EtfStrategyType::OPTION_INCOME,

            'distribution_frequency_id' =>

            DistributionFrequency::WEEKLY,

            'status_id' =>

            $statusId,

            'expense_ratio' => 0.99,

            'inception_date' =>

            '2026-01-01',

            'source' => 'manual',

            'website_url' =>

            'https://example.com',

            'notes' => null,

        ]);
    }

    private function createPriceHistory(

        Etf $etf

    ): void {

        EtfPriceHistory::create([

            'etf_id' =>

            $etf->id,

            'price_date' =>

            '2026-04-12',

            'close_price' =>

            10.0000,

            'volume' =>

            100000,

            'data_source_id' =>

            DataSource::MANUAL_ENTRY,

            'retrieved_at' =>

            now(),

        ]);

        EtfPriceHistory::create([

            'etf_id' =>

            $etf->id,

            'price_date' =>

            '2026-05-12',

            'close_price' =>

            12.0000,

            'volume' =>

            200000,

            'data_source_id' =>

            DataSource::MANUAL_ENTRY,

            'retrieved_at' =>

            now(),

        ]);
    }

    public function test_it_logs_aggregated_import_metrics()

    {

        $etfOne =

            $this->createEtf(

                'AAA',

                Status::ACTIVE

            );

        $etfTwo =

            $this->createEtf(

                'BBB',

                Status::ACTIVE

            );

        $this->createPriceHistory(

            $etfOne

        );

        $this->createPriceHistory(

            $etfTwo

        );

        $results =

            $this->handler

            ->handleCalculateEtfMetrics([

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

        // 2 ETFs × 6 ranges

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

            'Forced ETF metric recalculation executed successfully.',

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

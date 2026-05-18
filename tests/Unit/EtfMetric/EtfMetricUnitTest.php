<?php

namespace Tests\Unit\EtfMetric;

use App\Models\DataSource;
use App\Models\DistributionFrequency;
use App\Models\Etf;
use App\Models\EtfAumHistory;
use App\Models\EtfDividendHistory;
use App\Models\EtfIssuer;
use App\Models\EtfMetric;
use App\Models\EtfNavHistory;
use App\Models\EtfPriceHistory;
use App\Models\EtfStrategyType;
use App\Models\MetricDirection;
use App\Models\PerformanceRangeType;
use App\Models\Status;
use App\Services\EtfMetrics\CalculateEtfMetricService;
use Carbon\Carbon;
use Database\Seeders\DataSourceSeeder;
use Database\Seeders\DistributionFrequencySeeder;
use Database\Seeders\EtfIssuerSeeder;
use Database\Seeders\EtfStrategyTypeSeeder;
use Database\Seeders\MetricDirectionSeeder;
use Database\Seeders\PerformanceRangeTypeSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EtfMetricUnitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-05-12 12:00:00'));

        DB::table('etf_metrics')->truncate();
        DB::table('etf_dividend_histories')->truncate();
        DB::table('etf_nav_histories')->truncate();
        DB::table('etf_aum_histories')->truncate();
        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();

        DB::table('data_sources')->truncate();
        DB::table('performance_range_types')->truncate();
        DB::table('metric_directions')->truncate();
        DB::table('distribution_frequencies')->truncate();
        DB::table('etf_strategy_types')->truncate();
        DB::table('etf_issuers')->truncate();
        DB::table('statuses')->truncate();

        $this->seed(StatusSeeder::class);
        $this->seed(EtfIssuerSeeder::class);
        $this->seed(EtfStrategyTypeSeeder::class);
        $this->seed(DistributionFrequencySeeder::class);
        $this->seed(DataSourceSeeder::class);
        $this->seed(PerformanceRangeTypeSeeder::class);
        $this->seed(MetricDirectionSeeder::class);
    }

    protected function tearDown(): void
    {
        DB::table('etf_metrics')->truncate();
        DB::table('etf_dividend_histories')->truncate();
        DB::table('etf_nav_histories')->truncate();
        DB::table('etf_aum_histories')->truncate();
        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();

        DB::table('data_sources')->truncate();
        DB::table('performance_range_types')->truncate();
        DB::table('metric_directions')->truncate();
        DB::table('distribution_frequencies')->truncate();
        DB::table('etf_strategy_types')->truncate();
        DB::table('etf_issuers')->truncate();
        DB::table('statuses')->truncate();

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_calculates_full_thirty_day_etf_metric()
    {
        $etf = $this->createEtf();

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

        EtfDividendHistory::create([
            'etf_id' => $etf->id,
            'dividend_amount' => 0.5000,
            'ex_dividend_date' => '2026-04-20',
            'payment_date' => '2026-04-22',
            'data_source_id' => DataSource::MANUAL_ENTRY,
            'retrieved_at' => now(),
        ]);

        EtfDividendHistory::create([
            'etf_id' => $etf->id,
            'dividend_amount' => 0.2500,
            'ex_dividend_date' => '2026-05-05',
            'payment_date' => '2026-05-07',
            'data_source_id' => DataSource::MANUAL_ENTRY,
            'retrieved_at' => now(),
        ]);

        EtfNavHistory::create([
            'etf_id' => $etf->id,
            'nav_date' => '2026-04-12',
            'nav_per_share' => 10.0000,
            'data_source_id' => DataSource::MANUAL_ENTRY,
            'retrieved_at' => now(),
        ]);

        EtfNavHistory::create([
            'etf_id' => $etf->id,
            'nav_date' => '2026-05-12',
            'nav_per_share' => 10.5000,
            'data_source_id' => DataSource::MANUAL_ENTRY,
            'retrieved_at' => now(),
        ]);

        EtfAumHistory::create([
            'etf_id' => $etf->id,
            'aum_date' => '2026-04-12',
            'assets_under_management' => 100000000,
            'data_source_id' => DataSource::MANUAL_ENTRY,
            'retrieved_at' => now(),
        ]);

        EtfAumHistory::create([
            'etf_id' => $etf->id,
            'aum_date' => '2026-05-12',
            'assets_under_management' => 125000000,
            'data_source_id' => DataSource::MANUAL_ENTRY,
            'retrieved_at' => now(),
        ]);

        $metric = (new CalculateEtfMetricService)->calculate(
            $etf,
            PerformanceRangeType::THIRTY_DAY
        );

        $this->assertEquals($etf->id, $metric->etf_id);
        $this->assertEquals(PerformanceRangeType::THIRTY_DAY, $metric->performance_range_type_id);

        $this->assertEquals('2026-04-12', $metric->start_date->toDateString());
        $this->assertEquals('2026-05-12', $metric->end_date->toDateString());

        $this->assertEquals(10.0000, (float) $metric->start_price);
        $this->assertEquals(12.0000, (float) $metric->end_price);
        $this->assertEquals(2.0000, (float) $metric->price_change);
        $this->assertEquals(20.0000, (float) $metric->price_change_percentage);

        $this->assertEquals(0.7500, (float) $metric->dividends_paid);
        $this->assertEquals(2, $metric->dividend_count);
        $this->assertEquals(0.3750, (float) $metric->average_dividend);

        $this->assertEquals(27.5000, (float) $metric->total_return_percentage);

        $this->assertEquals(10.0000, (float) $metric->start_nav);
        $this->assertEquals(10.5000, (float) $metric->end_nav);
        $this->assertEquals(0.5000, (float) $metric->nav_change);
        $this->assertEquals(12.5000, (float) $metric->nav_erosion_percentage);
        $this->assertEquals(MetricDirection::IMPROVING, $metric->nav_direction_id);

        $this->assertEquals(100000000, $metric->start_aum);
        $this->assertEquals(125000000, $metric->end_aum);
        $this->assertEquals(25000000, $metric->aum_change);
        $this->assertEquals(25.0000, (float) $metric->aum_change_percentage);
        $this->assertEquals(MetricDirection::GROWING, $metric->aum_direction_id);

        $this->assertNotNull($metric->calculated_at);
    }

    public function test_it_calculates_eroding_nav_and_shrinking_aum()
    {
        $etf = $this->createEtf();

        EtfPriceHistory::create([
            'etf_id' => $etf->id,
            'price_date' => '2026-04-12',
            'close_price' => 20.0000,
        ]);

        EtfPriceHistory::create([
            'etf_id' => $etf->id,
            'price_date' => '2026-05-12',
            'close_price' => 18.0000,
        ]);

        EtfDividendHistory::create([
            'etf_id' => $etf->id,
            'dividend_amount' => 0.5000,
            'ex_dividend_date' => '2026-05-01',
        ]);

        EtfNavHistory::create([
            'etf_id' => $etf->id,
            'nav_date' => '2026-04-12',
            'nav_per_share' => 20.0000,
        ]);

        EtfNavHistory::create([
            'etf_id' => $etf->id,
            'nav_date' => '2026-05-12',
            'nav_per_share' => 18.0000,
        ]);

        EtfAumHistory::create([
            'etf_id' => $etf->id,
            'aum_date' => '2026-04-12',
            'assets_under_management' => 100000000,
        ]);

        EtfAumHistory::create([
            'etf_id' => $etf->id,
            'aum_date' => '2026-05-12',
            'assets_under_management' => 90000000,
        ]);

        $metric = (new CalculateEtfMetricService)->calculate(
            $etf,
            PerformanceRangeType::THIRTY_DAY
        );

        $this->assertEquals(-10.0000, (float) $metric->price_change_percentage);
        $this->assertEquals(-7.5000, (float) $metric->total_return_percentage);

        $this->assertEquals(-2.0000, (float) $metric->nav_change);
        $this->assertEquals(-7.5000, (float) $metric->nav_erosion_percentage);
        $this->assertEquals(MetricDirection::ERODING, $metric->nav_direction_id);

        $this->assertEquals(-10000000, $metric->aum_change);
        $this->assertEquals(-10.0000, (float) $metric->aum_change_percentage);
        $this->assertEquals(MetricDirection::SHRINKING, $metric->aum_direction_id);
    }

    public function test_it_handles_missing_nav_and_aum_data()
    {
        $etf = $this->createEtf();

        EtfPriceHistory::create([
            'etf_id' => $etf->id,
            'price_date' => '2026-04-12',
            'close_price' => 10.0000,
        ]);

        EtfPriceHistory::create([
            'etf_id' => $etf->id,
            'price_date' => '2026-05-12',
            'close_price' => 11.0000,
        ]);

        $metric = (new CalculateEtfMetricService)->calculate(
            $etf,
            PerformanceRangeType::THIRTY_DAY
        );

        $this->assertEquals(10.0000, (float) $metric->price_change_percentage);

        $this->assertNull($metric->start_nav);
        $this->assertNull($metric->end_nav);
        $this->assertNull($metric->nav_change);
        $this->assertNull($metric->nav_erosion_percentage);
        $this->assertNull($metric->nav_direction_id);

        $this->assertNull($metric->start_aum);
        $this->assertNull($metric->end_aum);
        $this->assertNull($metric->aum_change);
        $this->assertNull($metric->aum_change_percentage);
        $this->assertNull($metric->aum_direction_id);
    }

    public function test_it_updates_existing_metric_instead_of_creating_duplicate()
    {
        $etf = $this->createEtf();

        EtfPriceHistory::create([
            'etf_id' => $etf->id,
            'price_date' => '2026-04-12',
            'close_price' => 10.0000,
        ]);

        $endPrice = EtfPriceHistory::create([
            'etf_id' => $etf->id,
            'price_date' => '2026-05-12',
            'close_price' => 12.0000,
        ]);

        $service = new CalculateEtfMetricService;

        $service->calculate($etf, PerformanceRangeType::THIRTY_DAY);

        $this->assertEquals(1, EtfMetric::count());

        $endPrice->close_price = 14.0000;
        $endPrice->save();

        $metric = $service->calculate($etf, PerformanceRangeType::THIRTY_DAY);

        $this->assertEquals(1, EtfMetric::count());
        $this->assertEquals(14.0000, (float) $metric->end_price);
        $this->assertEquals(4.0000, (float) $metric->price_change);
        $this->assertEquals(40.0000, (float) $metric->price_change_percentage);
    }

    public function test_it_uses_correct_start_dates_for_each_performance_range_type()
    {
        $etf = $this->createEtf();

        /*
    |--------------------------------------------------------------------------
    | Historical Price Data
    |--------------------------------------------------------------------------
    */

        EtfPriceHistory::create([
            'etf_id' => $etf->id,
            'price_date' => '2025-01-01',
            'close_price' => 10.0000,
        ]);

        EtfPriceHistory::create([
            'etf_id' => $etf->id,
            'price_date' => '2025-05-12',
            'close_price' => 11.0000,
        ]);

        EtfPriceHistory::create([
            'etf_id' => $etf->id,
            'price_date' => '2026-01-01',
            'close_price' => 12.0000,
        ]);

        EtfPriceHistory::create([
            'etf_id' => $etf->id,
            'price_date' => '2026-02-11',
            'close_price' => 13.0000,
        ]);

        EtfPriceHistory::create([
            'etf_id' => $etf->id,
            'price_date' => '2026-04-12',
            'close_price' => 14.0000,
        ]);

        EtfPriceHistory::create([
            'etf_id' => $etf->id,
            'price_date' => '2026-05-07',
            'close_price' => 15.0000,
        ]);

        EtfPriceHistory::create([
            'etf_id' => $etf->id,
            'price_date' => '2026-05-12',
            'close_price' => 16.0000,
        ]);

        $service = new CalculateEtfMetricService;

        $expectedRanges = [

            PerformanceRangeType::FIVE_DAY => '2026-05-07',

            PerformanceRangeType::THIRTY_DAY => '2026-04-12',

            PerformanceRangeType::NINETY_DAY => '2026-02-11',

            PerformanceRangeType::YEAR_TO_DATE => '2026-01-01',

            PerformanceRangeType::ONE_YEAR => '2025-05-12',

            /*
        |--------------------------------------------------------------------------
        | MAX should use first available historical record
        |--------------------------------------------------------------------------
        */

            PerformanceRangeType::MAX => '2025-01-01',

        ];

        foreach ($expectedRanges as $rangeTypeId => $expectedStartDate) {

            $metric = $service->calculate($etf, $rangeTypeId);

            $this->assertEquals(
                $expectedStartDate,
                $metric->start_date->format('Y-m-d')
            );

            $this->assertEquals(
                '2026-05-12',
                $metric->end_date->toDateString()
            );
        }
    }

    public function test_it_returns_null_when_start_price_is_missing()
    {
        $etf = $this->createEtf();

        EtfPriceHistory::create([
            'etf_id' => $etf->id,
            'price_date' => now()->subDays(40)->toDateString(),
            'close_price' => 11.0000,
        ]);

        $metric = (new CalculateEtfMetricService)->calculate(
            $etf,
            PerformanceRangeType::THIRTY_DAY
        );

        $this->assertNull($metric);

        $this->assertEquals(0, EtfMetric::count());
    }

    public function test_it_returns_null_when_end_price_is_missing()
    {
        $etf = $this->createEtf();

        EtfPriceHistory::create([
            'etf_id' => $etf->id,
            'price_date' => now()->addDays(1)->toDateString(),
            'close_price' => 10.0000,
        ]);

        $metric = (new CalculateEtfMetricService)->calculate(
            $etf,
            PerformanceRangeType::THIRTY_DAY
        );

        $this->assertNull($metric);

        $this->assertEquals(0, EtfMetric::count());
    }

    public function test_it_still_creates_metric_when_nav_and_aum_are_missing()
    {
        $etf = $this->createEtf();

        EtfPriceHistory::create([
            'etf_id' => $etf->id,
            'price_date' => now()->subDays(30)->toDateString(),
            'close_price' => 10.0000,
        ]);

        EtfPriceHistory::create([
            'etf_id' => $etf->id,
            'price_date' => now()->toDateString(),
            'close_price' => 11.0000,
        ]);

        $metric = (new CalculateEtfMetricService)->calculate(
            $etf,
            PerformanceRangeType::THIRTY_DAY
        );

        $this->assertNotNull($metric);
        $this->assertEquals(10.0000, (float) $metric->price_change_percentage);
        $this->assertNull($metric->start_nav);
        $this->assertNull($metric->end_nav);
        $this->assertNull($metric->start_aum);
        $this->assertNull($metric->end_aum);
    }


    private function createEtf(): Etf
    {
        return Etf::create([
            'symbol' => 'TETF',
            'fund_name' => 'Test ETF',
            'etf_issuer_id' => EtfIssuer::YIELDMAX,
            'etf_strategy_type_id' => EtfStrategyType::OPTION_INCOME,
            'distribution_frequency_id' => DistributionFrequency::WEEKLY,
            'status_id' => Status::ACTIVE,
            'expense_ratio' => 0.99,
            'inception_date' => '2026-01-01',
            'source' => 'manual',
            'website_url' => 'https://example.com',
            'notes' => null,
        ]);
    }
}

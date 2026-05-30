<?php

namespace Tests\Unit\Queries\Securities;

use App\Models\PerformanceRangeType;
use App\Models\Security;
use App\Models\SecurityDetail;
use App\Models\SecurityDividendHistory;
use App\Models\SecurityMetric;
use App\Queries\Securities\SecuritySignalsQuery;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SecuritySignalsQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        SecurityMetric::truncate();
        SecurityDividendHistory::truncate();
        Security::truncate();
        SecurityDetail::truncate();
    }

    protected function tearDown(): void
    {
        SecurityMetric::truncate();
        SecurityDividendHistory::truncate();
        Security::truncate();
        SecurityDetail::truncate();

        parent::tearDown();
    }

    public function test_it_returns_unknown_signals_when_no_data_exists()
    {
        $security = Security::factory()->create();

        $results = app(
            SecuritySignalsQuery::class
        )->getData(
            $security->id
        );

        $this->assertEquals(
            'unknown',
            $results['distribution_growth']['status']
        );

        $this->assertEquals(
            'unknown',
            $results['aum_growth']['status']
        );

        $this->assertEquals(
            'unknown',
            $results['nav_stability']['status']
        );
    }

    public function test_it_returns_strong_distribution_growth_signal()
    {
        $security = Security::factory()->create();

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'payment_date' => now()->subMonths(18),
            'dividend_amount' => 1.00,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'payment_date' => now()->subMonths(15),
            'dividend_amount' => 1.00,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'payment_date' => now()->subMonths(10),
            'dividend_amount' => 1.50,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'payment_date' => now()->subMonths(3),
            'dividend_amount' => 1.50,
        ]);

        $results = app(
            SecuritySignalsQuery::class
        )->getData(
            $security->id
        );

        $this->assertEquals(
            'strong_growth',
            $results['distribution_growth']['status']
        );

        $this->assertEquals(
            50.0000,
            $results['distribution_growth']['value']
        );

        $this->assertEquals(
            3.0000,
            $results['distribution_growth']['recent_year_distributions']
        );

        $this->assertEquals(
            2.0000,
            $results['distribution_growth']['previous_year_distributions']
        );
    }

    public function test_it_returns_distribution_decline_signal()
    {
        $security = Security::factory()->create();

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'payment_date' => now()->subMonths(18),
            'dividend_amount' => 2.00,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'payment_date' => now()->subMonths(15),
            'dividend_amount' => 2.00,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'payment_date' => now()->subMonths(10),
            'dividend_amount' => 1.50,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'payment_date' => now()->subMonths(3),
            'dividend_amount' => 1.50,
        ]);

        $results = app(
            SecuritySignalsQuery::class
        )->getData(
            $security->id
        );

        $this->assertEquals(
            'decline',
            $results['distribution_growth']['status']
        );

        $this->assertEquals(
            -25.0000,
            $results['distribution_growth']['value']
        );
    }

    public function test_it_returns_strong_aum_inflow_signal()
    {
        $security = Security::factory()->create();

        SecurityMetric::factory()->create([
            'security_id' => $security->id,
            'performance_range_type_id' => PerformanceRangeType::THIRTY_DAY,
            'aum_change_percentage' => 12.5000,
        ]);

        $results = app(
            SecuritySignalsQuery::class
        )->getData(
            $security->id
        );

        $this->assertEquals(
            'strong_inflow',
            $results['aum_growth']['status']
        );

        $this->assertEquals(
            12.5000,
            $results['aum_growth']['value']
        );
    }

    public function test_it_returns_watch_nav_signal()
    {
        $security = Security::factory()->create();

        SecurityMetric::factory()->create([
            'security_id' => $security->id,
            'performance_range_type_id' => PerformanceRangeType::MAX,
            'nav_erosion_percentage' => -15.0000,
        ]);

        $results = app(
            SecuritySignalsQuery::class
        )->getData(
            $security->id
        );

        $this->assertEquals(
            'watch',
            $results['nav_stability']['status']
        );

        $this->assertEquals(
            -15.0000,
            $results['nav_stability']['value']
        );
    }
}

<?php

namespace Tests\Unit\Queries\Securities;

use App\Models\PerformanceRangeType;
use App\Models\Security;
use App\Models\SecurityDetail;
use App\Models\SecurityDividendHistory;
use App\Models\SecurityMetric;
use App\Models\SecurityPriceHistory;
use App\Queries\Securities\SecurityDetailsQuery;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SecurityDetailsQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        SecurityPriceHistory::truncate();
        SecurityDividendHistory::truncate();
        SecurityMetric::truncate();
        Security::truncate();
        SecurityDetail::truncate();
    }

    protected function tearDown(): void
    {
        SecurityPriceHistory::truncate();
        SecurityDividendHistory::truncate();
        SecurityMetric::truncate();
        Security::truncate();
        SecurityDetail::truncate();

        parent::tearDown();
    }

    public function test_it_returns_complete_security_details()
    {
        $security = Security::factory()->create([
            'symbol' => 'SPY',
        ]);

        SecurityMetric::factory()->create([
            'security_id' => $security->id,
            'performance_range_type_id' => PerformanceRangeType::THIRTY_DAY,
            'aum_change_percentage' => 10,
        ]);

        SecurityMetric::factory()->create([
            'security_id' => $security->id,
            'performance_range_type_id' => PerformanceRangeType::MAX,
            'nav_erosion_percentage' => -2,
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => now()->subDays(5),
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'payment_date' => now()->subMonths(18),
            'dividend_amount' => 1.00,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'payment_date' => now()->subMonths(6),
            'dividend_amount' => 1.50,
        ]);

        $results = app(
            SecurityDetailsQuery::class
        )->getData(
            'spy',
            PerformanceRangeType::THIRTY_DAY,
            now()->subYear()->toDateString()
        );

        $this->assertArrayHasKey(
            'security',
            $results
        );

        $this->assertArrayHasKey(
            'metrics',
            $results
        );

        $this->assertArrayHasKey(
            'chart_rows',
            $results
        );

        $this->assertArrayHasKey(
            'signals',
            $results
        );

        $this->assertArrayHasKey(
            'dividend_history',
            $results
        );

        $this->assertEquals(
            'SPY',
            $results['security']['symbol']
        );

        $this->assertNotEmpty(
            $results['chart_rows']
        );

        $this->assertIsArray(
            $results['signals']
        );

        $this->assertNotEmpty(
            $results['dividend_history']
        );
    }

    public function test_it_uses_cache()
    {
        $security = Security::factory()->create([
            'symbol' => 'SPY',
        ]);

        $startDate = now()
            ->subYear()
            ->toDateString();

        $firstResult = app(
            SecurityDetailsQuery::class
        )->getData(
            'SPY',
            PerformanceRangeType::THIRTY_DAY,
            $startDate
        );

        Security::where(
            'id',
            $security->id
        )->update([
            'symbol' => 'UPDATED',
        ]);

        $secondResult = app(
            SecurityDetailsQuery::class
        )->getData(
            'SPY',
            PerformanceRangeType::THIRTY_DAY,
            $startDate
        );

        $this->assertEquals(
            $firstResult['security']['symbol'],
            $secondResult['security']['symbol']
        );
    }
}

<?php

namespace Tests\Unit\Queries\Securities;

use App\Models\PerformanceRangeType;
use App\Models\Security;
use App\Models\SecurityMetric;
use App\Models\SecurityPriceHistory;
use App\Models\Status;
use App\Queries\Securities\SecurityMetricsQuery;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecurityMetricsQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        DB::table('security_metrics')->truncate();
        DB::table('security_price_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
    }

    protected function tearDown(): void
    {
        Cache::flush();

        DB::table('security_metrics')->truncate();
        DB::table('security_price_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_metric_data(): void
    {
        $security = Security::factory()->create([
            'symbol' => 'ABNY',
            'status_id' => Status::ACTIVE,
        ]);

        SecurityMetric::factory()->create([
            'security_id' => $security->id,
            'performance_range_type_id' => PerformanceRangeType::NINETY_DAY,
            'aum_change_percentage' => 7.25,
            'total_return_percentage' => 18.50,
            'nav_erosion_percentage' => 1.50,
            'price_change_percentage' => 12.75,
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2026-05-01',
            'close_price' => 39.76,
        ]);

        $result = app(
            SecurityMetricsQuery::class
        )->getData(
            $security->id,
            PerformanceRangeType::NINETY_DAY
        );

        $this->assertSame(
            39.76,
            (float) $result['current_price']
        );

        $this->assertSame(
            7.25,
            (float) $result['aum_flow']
        );

        $this->assertSame(
            18.50,
            (float) $result['total_return']
        );

        $this->assertSame(
            1.50,
            (float) $result['nav_erosion_percentage']
        );

        $this->assertSame(
            12.75,
            (float) $result['price_change_percentage']
        );

        $this->assertSame(
            'Stable',
            $result['nav_health']
        );
    }

    public function test_it_returns_watch_nav_health(): void
    {
        $security = Security::factory()->create([
            'status_id' => Status::ACTIVE,
        ]);

        SecurityMetric::factory()->create([
            'security_id' => $security->id,
            'performance_range_type_id' => PerformanceRangeType::NINETY_DAY,
            'nav_erosion_percentage' => -15,
        ]);

        $result = app(
            SecurityMetricsQuery::class
        )->getData(
            $security->id,
            PerformanceRangeType::NINETY_DAY
        );

        $this->assertSame(
            'Watch',
            $result['nav_health']
        );
    }

    public function test_it_returns_mixed_nav_health(): void
    {
        $security = Security::factory()->create([
            'status_id' => Status::ACTIVE,
        ]);

        SecurityMetric::factory()->create([
            'security_id' => $security->id,
            'performance_range_type_id' => PerformanceRangeType::NINETY_DAY,
            'nav_erosion_percentage' => -5,
        ]);

        $result = app(
            SecurityMetricsQuery::class
        )->getData(
            $security->id,
            PerformanceRangeType::NINETY_DAY
        );

        $this->assertSame(
            'Mixed',
            $result['nav_health']
        );
    }

    public function test_it_returns_unknown_when_metric_is_missing(): void
    {
        $security = Security::factory()->create([
            'status_id' => Status::ACTIVE,
        ]);

        $result = app(
            SecurityMetricsQuery::class
        )->getData(
            $security->id,
            PerformanceRangeType::NINETY_DAY
        );

        $this->assertSame(
            'Unknown',
            $result['nav_health']
        );

        $this->assertNull(
            $result['aum_flow']
        );

        $this->assertNull(
            $result['total_return']
        );
    }

    public function test_it_uses_latest_price(): void
    {
        $security = Security::factory()->create([
            'status_id' => Status::ACTIVE,
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2026-04-01',
            'close_price' => 20,
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2026-05-01',
            'close_price' => 30,
        ]);

        $result = app(
            SecurityMetricsQuery::class
        )->getData(
            $security->id,
            PerformanceRangeType::NINETY_DAY
        );

        $this->assertSame(
            30.0,
            (float) $result['current_price']
        );
    }
}

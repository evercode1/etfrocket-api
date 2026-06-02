<?php

namespace Tests\Unit\AiSignals\Watchlists;

use App\Models\PerformanceRangeType;
use App\Models\Security;
use App\Models\SecurityMetric;
use App\Services\AI\AiSignals\Watchlists\EtfWatchlistService;
use Database\Seeders\PerformanceRangeTypeSeeder;
use Database\Seeders\StatusSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EtfWatchlistServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('security_metrics')
            ->truncate();

        DB::table('security_details')
            ->truncate();

        DB::table('securities')
            ->truncate();

        $this->seed([
            StatusSeeder::class,
            PerformanceRangeTypeSeeder::class,
        ]);
    }

    protected function tearDown(): void
    {
        DB::table('security_metrics')
            ->truncate();

        DB::table('security_details')
            ->truncate();

        DB::table('securities')
            ->truncate();

        parent::tearDown();
    }

    public function test_it_builds_watchlists_in_correct_order(): void
    {
        $securityA = Security::factory()->create([
            'symbol' => 'AAA',
        ]);

        $securityB = Security::factory()->create([
            'symbol' => 'BBB',
        ]);

        $securityC = Security::factory()->create([
            'symbol' => 'CCC',
        ]);

        SecurityMetric::create([

            'security_id' => $securityA->id,

            'performance_range_type_id' => PerformanceRangeType::THIRTY_DAY,

            'total_return_percentage' => 20,

            'price_change_percentage' => 8,

            'aum_change_percentage' => 5,

            'nav_erosion_percentage' => 0,

        ]);

        SecurityMetric::create([

            'security_id' => $securityB->id,

            'performance_range_type_id' => PerformanceRangeType::THIRTY_DAY,

            'total_return_percentage' => 15,

            'price_change_percentage' => 12,

            'aum_change_percentage' => 20,

            'nav_erosion_percentage' => -2,

        ]);

        SecurityMetric::create([

            'security_id' => $securityC->id,

            'performance_range_type_id' => PerformanceRangeType::THIRTY_DAY,

            'total_return_percentage' => 10,

            'price_change_percentage' => 4,

            'aum_change_percentage' => 10,

            'nav_erosion_percentage' => -5,

        ]);

        $data = app(
            EtfWatchlistService::class
        )->getData();

        $this->assertArrayHasKey(
            'top_performers',
            $data
        );

        $this->assertArrayHasKey(
            'price_movers',
            $data
        );

        $this->assertArrayHasKey(
            'aum_growth',
            $data
        );

        $this->assertArrayHasKey(
            'nav_health',
            $data
        );

        /*
        |--------------------------------------------------------------------------
        | Top Performers
        |--------------------------------------------------------------------------
        */

        $this->assertEquals(
            'AAA',
            $data['top_performers'][0]['symbol']
        );

        $this->assertEquals(
            'BBB',
            $data['top_performers'][1]['symbol']
        );

        $this->assertEquals(
            'CCC',
            $data['top_performers'][2]['symbol']
        );

        /*
        |--------------------------------------------------------------------------
        | Price Movers
        |--------------------------------------------------------------------------
        */

        $this->assertEquals(
            'BBB',
            $data['price_movers'][0]['symbol']
        );

        $this->assertEquals(
            'AAA',
            $data['price_movers'][1]['symbol']
        );

        $this->assertEquals(
            'CCC',
            $data['price_movers'][2]['symbol']
        );

        /*
        |--------------------------------------------------------------------------
        | AUM Growth
        |--------------------------------------------------------------------------
        */

        $this->assertEquals(
            'BBB',
            $data['aum_growth'][0]['symbol']
        );

        $this->assertEquals(
            'CCC',
            $data['aum_growth'][1]['symbol']
        );

        $this->assertEquals(
            'AAA',
            $data['aum_growth'][2]['symbol']
        );

        /*
        |--------------------------------------------------------------------------
        | NAV Health
        |--------------------------------------------------------------------------
        */

        $this->assertEquals(
            'AAA',
            $data['nav_health'][0]['symbol']
        );

        $this->assertEquals(
            'BBB',
            $data['nav_health'][1]['symbol']
        );

        $this->assertEquals(
            'CCC',
            $data['nav_health'][2]['symbol']
        );
    }
}

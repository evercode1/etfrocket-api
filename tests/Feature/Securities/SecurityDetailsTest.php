<?php

namespace Tests\Feature\Securities;

use App\Models\PerformanceRangeType;
use App\Models\Security;
use App\Models\SecurityDetail;
use App\Models\SecurityDividendHistory;
use App\Models\SecurityMetric;
use App\Models\SecurityPriceHistory;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityDetailsTest extends TestCase
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
        User::truncate();

        $user = User::factory()->create();

        Sanctum::actingAs(

            $user,

            ['*']

        );
    }

    protected function tearDown(): void
    {
        SecurityPriceHistory::truncate();
        SecurityDividendHistory::truncate();
        SecurityMetric::truncate();
        Security::truncate();
        SecurityDetail::truncate();
        User::truncate();

        parent::tearDown();
    }

    public function test_it_returns_security_details()
    {
        $security = Security::factory()->create([
            'symbol' => 'SPY',
        ]);

        SecurityMetric::factory()->create([
            'security_id' => $security->id,
            'performance_range_type_id' => PerformanceRangeType::THIRTY_DAY,
            'aum_change_percentage' => 10.00,
        ]);

        SecurityMetric::factory()->create([
            'security_id' => $security->id,
            'performance_range_type_id' => PerformanceRangeType::MAX,
            'nav_erosion_percentage' => -2.00,
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

        $response = $this->getJson(

            '/api/security-details/SPY?'.

            http_build_query([

                'performance_range_type_id' => PerformanceRangeType::THIRTY_DAY,

                'start_date' => now()->subYear()->toDateString(),

            ])

        );

        $response->assertOk();

        $response->assertJson([

            'success' => true,

        ]);

        $response->assertJsonStructure([

            'success',

            'data' => [

                'security',

                'metrics',

                'chart_rows',

                'signals',

                'dividend_history',

            ],

        ]);

        $payload = $response->json('data');

        $this->assertEquals(
            'SPY',
            $payload['security']['symbol']
        );

        $this->assertIsArray(
            $payload['metrics']
        );

        $this->assertIsArray(
            $payload['chart_rows']
        );

        $this->assertIsArray(
            $payload['signals']
        );

        $this->assertIsArray(
            $payload['dividend_history']
        );
    }

    public function test_it_returns_not_found_for_invalid_symbol()
    {
        $response = $this->getJson(

            '/api/security-details/DOESNOTEXIST?'.

            http_build_query([

                'performance_range_type_id' => PerformanceRangeType::THIRTY_DAY,

                'start_date' => now()->subYear()->toDateString(),

            ])

        );

        $response->assertStatus(404);
    }
}

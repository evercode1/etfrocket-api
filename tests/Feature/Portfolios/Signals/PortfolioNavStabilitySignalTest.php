<?php

namespace Tests\Feature\Portfolios\Signals;

use App\Models\PerformanceRangeType;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Security;
use App\Models\SecurityMetric;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PortfolioNavStabilitySignalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('security_metrics')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('security_metrics')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_authenticated_user_can_get_nav_stability_signal(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $security = Security::factory()->create([
            'symbol' => 'RISK',
            'status_id' => Status::ACTIVE,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'transaction_type_id' => 1,
            'shares' => 100,
            'price_per_share' => 25,
            'transaction_date' => '2026-01-01',
        ]);

        SecurityMetric::factory()->create([
            'security_id' => $security->id,
            'performance_range_type_id' => PerformanceRangeType::MAX,
            'start_date' => '2026-01-01',
            'end_date' => '2026-05-01',
            'start_nav' => '10.0000',
            'end_nav' => '8.8000',
            'nav_change' => '-1.2000',
            'nav_erosion_percentage' => '-12.0000',
            'nav_direction_id' => 2,
        ]);

        $response = $this->getJson(
            "/api/portfolio-nav-stability-signal/{$portfolio->id}"
        );

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
        ]);

        $response->assertJsonPath('data.has_holdings', true);
        $response->assertJsonPath('data.has_data', true);
        $response->assertJsonPath('data.nav_health', 'Watch');
        $response->assertJsonPath('data.watch_count', 1);
        $response->assertJsonPath('data.mixed_count', 0);
        $response->assertJsonPath('data.stable_count', 0);
        $response->assertJsonPath('data.affected_securities.0', 'RISK');
        $response->assertJsonPath('data.watch_list.0.symbol', 'RISK');

        $this->assertEquals(
            -12.0,
            $response->json('data.worst_nav_erosion_percentage')
        );
    }

    public function test_authenticated_user_gets_empty_nav_signal_when_no_holdings_exist(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $response = $this->getJson(
            "/api/portfolio-nav-stability-signal/{$portfolio->id}"
        );

        $response->assertStatus(200);

        $response->assertJsonPath('data.has_holdings', false);
        $response->assertJsonPath('data.has_data', false);
        $response->assertJsonPath('data.nav_health', 'No Holdings');
    }

    public function test_guest_cannot_get_nav_stability_signal(): void
    {
        $response = $this->getJson('/api/portfolio-nav-stability-signal/1');

        $response->assertStatus(401);
    }

    public function test_user_cannot_get_nav_stability_signal_for_another_users_portfolio(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $otherUser->id,
            'status_id' => Status::ACTIVE,
        ]);

        $response = $this->getJson(
            "/api/portfolio-nav-stability-signal/{$portfolio->id}"
        );

        $response->assertStatus(500);

        $response->assertJson([
            'success' => false,
            'message' => 'Oops, something went wrong. Please try again later.',
        ]);
    }
}

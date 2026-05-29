<?php

namespace Tests\Feature\Portfolios\Signals;

use App\Models\PerformanceRangeType;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Security;
use App\Models\SecurityDetail;
use App\Models\SecurityMetric;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PortfolioAumGrowthSignalTest extends TestCase
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

    public function test_authenticated_user_can_get_aum_growth_signal(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Income Portfolio',
            'status_id' => Status::ACTIVE,
        ]);

        $security = Security::create([
            'symbol' => 'NVII',

            'status_id' => Status::ACTIVE,
        ]);

        SecurityDetail::factory()->create([
            'security_id' => $security->id,
            'security_name' => 'NVII_name',
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
            'performance_range_type_id' => PerformanceRangeType::THIRTY_DAY,
            'start_date' => '2026-04-01',
            'end_date' => '2026-05-01',
            'start_aum' => 100000000,
            'end_aum' => 125000000,
            'aum_change' => 25000000,
            'aum_change_percentage' => '25.0000',
            'aum_direction_id' => 1,
        ]);

        $response = $this->getJson(
            "/api/portfolio-aum-growth-signal/{$portfolio->id}"
        );

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
        ]);

        $response->assertJsonPath('data.has_holdings', true);
        $response->assertJsonPath('data.has_data', true);
        $response->assertJsonPath('data.range_type_id', PerformanceRangeType::THIRTY_DAY);
        $response->assertJsonPath('data.positive_flow_count', 1);
        $response->assertJsonPath('data.negative_flow_count', 0);
        $response->assertJsonPath('data.affected_securities.0', 'NVII');
        $response->assertJsonPath('data.strongest_inflows.0.symbol', 'NVII');
        $response->assertJsonPath('data.strongest_inflows.0.start_aum', 100000000);
        $response->assertJsonPath('data.strongest_inflows.0.end_aum', 125000000);
        $response->assertJsonPath('data.strongest_inflows.0.aum_change', 25000000);

        $this->assertEquals(
            25.0,
            $response->json('data.strongest_inflows.0.aum_change_percentage')
        );
    }

    public function test_authenticated_user_gets_empty_aum_growth_signal_when_no_holdings_exist(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Empty Portfolio',
            'status_id' => Status::ACTIVE,
        ]);

        $response = $this->getJson(
            "/api/portfolio-aum-growth-signal/{$portfolio->id}"
        );

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
        ]);

        $response->assertJsonPath('data.has_holdings', false);
        $response->assertJsonPath('data.has_data', false);
        $response->assertJsonPath('data.range_type_id', PerformanceRangeType::THIRTY_DAY);
        $response->assertJsonPath('data.positive_flow_count', 0);
        $response->assertJsonPath('data.negative_flow_count', 0);

        $this->assertSame([], $response->json('data.strongest_inflows'));
        $this->assertSame([], $response->json('data.strongest_outflows'));
        $this->assertSame([], $response->json('data.affected_securities'));
        $this->assertSame([], $response->json('data.all_rows'));
    }

    public function test_guest_cannot_get_aum_growth_signal(): void
    {
        $response = $this->getJson('/api/portfolio-aum-growth-signal/1');

        $response->assertStatus(401);
    }

    public function test_user_cannot_get_aum_growth_signal_for_another_users_portfolio(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $otherUser->id,
            'status_id' => Status::ACTIVE,
        ]);

        $response = $this->getJson(
            "/api/portfolio-aum-growth-signal/{$portfolio->id}"
        );

        $response->assertStatus(500);

        $response->assertJson([
            'success' => false,
            'message' => 'Oops, something went wrong. Please try again later.',
        ]);
    }
}

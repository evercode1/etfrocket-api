<?php

namespace Tests\Feature\PortfolioSignals;

use App\Models\Etf;
use App\Models\EtfMetric;
use App\Models\PerformanceRangeType;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Status;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PortfolioDistributionGrowthSignalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-05-20'));

        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('etf_metrics')->truncate();
        DB::table('etfs')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('etf_metrics')->truncate();
        DB::table('etfs')->truncate();
        DB::table('users')->truncate();

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_authenticated_user_can_get_distribution_growth_signal(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Income Portfolio',
            'status_id' => Status::ACTIVE,
        ]);

        $etf = Etf::factory()->create([
            'symbol' => 'NVII',
            'fund_name' => 'NVII Test ETF',
            'status_id' => Status::ACTIVE,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $etf->id,
            'transaction_type_id' => 1,
            'shares' => 100,
            'price_per_share' => 25,
            'transaction_date' => '2026-01-01',
        ]);

        EtfMetric::factory()->create([
            'etf_id' => $etf->id,
            'performance_range_type_id' => PerformanceRangeType::THIRTY_DAY,
            'average_dividend' => '0.6000',
            'dividend_count' => 4,
        ]);

        EtfMetric::factory()->create([
            'etf_id' => $etf->id,
            'performance_range_type_id' => PerformanceRangeType::NINETY_DAY,
            'average_dividend' => '0.5000',
            'dividend_count' => 12,
        ]);

        $response = $this->getJson(
            "/api/portfolio-distribution-growth-signal/{$portfolio->id}"
        );

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
        ]);

        $response->assertJsonPath('data.has_holdings', true);
        $response->assertJsonPath('data.has_data', true);
        $response->assertJsonPath('data.growth_count', 1);

        $this->assertEquals(
            10.0,
            $response->json('data.portfolio_income_impact')
        );

        $response->assertJsonPath(
            'data.affected_etfs.0',
            'NVII'
        );

        $response->assertJsonPath(
            'data.top_contributors.0.symbol',
            'NVII'
        );

        $this->assertEquals(
            20.0,
            $response->json('data.top_contributors.0.growth_percentage')
        );

        $this->assertEquals(
            10.0,
            $response->json('data.top_contributors.0.estimated_income_impact')
        );
    }

    public function test_authenticated_user_gets_empty_distribution_growth_signal_when_no_holdings_exist(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Empty Portfolio',
            'status_id' => Status::ACTIVE,
        ]);

        $response = $this->getJson(
            "/api/portfolio-distribution-growth-signal/{$portfolio->id}"
        );

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
        ]);

        $response->assertJsonPath('data.has_holdings', false);
        $response->assertJsonPath('data.has_data', false);
        $response->assertJsonPath('data.growth_count', 0);

        $this->assertEquals(
            0.0,
            $response->json('data.portfolio_income_impact')
        );

        $this->assertSame([], $response->json('data.affected_etfs'));
        $this->assertSame([], $response->json('data.top_contributors'));
        $this->assertSame([], $response->json('data.all_rows'));
    }

    public function test_guest_cannot_get_distribution_growth_signal(): void
    {
        $response = $this->getJson(
            '/api/portfolio-distribution-growth-signal/1'
        );

        $response->assertStatus(401);
    }

    public function test_user_cannot_get_distribution_growth_signal_for_another_users_portfolio(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $otherUser->id,
            'status_id' => Status::ACTIVE,
        ]);

        $response = $this->getJson(
            "/api/portfolio-distribution-growth-signal/{$portfolio->id}"
        );

        $response->assertStatus(500);

        $response->assertJson([
            'success' => false,
            'message' => 'Oops, something went wrong. Please try again later.',
        ]);
    }
}

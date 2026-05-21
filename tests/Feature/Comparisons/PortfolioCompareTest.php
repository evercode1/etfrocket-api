<?php

namespace Tests\Feature\Comparisons;

use App\Models\Etf;
use App\Models\EtfMetric;
use App\Models\EtfPriceHistory;
use App\Models\PerformanceRangeType;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Status;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PortfolioCompareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-05-20'));

        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('etf_metrics')->truncate();
        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('etf_metrics')->truncate();
        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();
        DB::table('users')->truncate();

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_authenticated_user_can_get_portfolio_compare_data(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Main Portfolio',
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
            'shares' => 10,
            'price_per_share' => 25,
            'transaction_date' => '2026-01-01',
        ]);

        EtfPriceHistory::factory()->create([
            'etf_id' => $etf->id,
            'price_date' => '2026-05-20',
            'close_price' => '30.0000',
            'volume' => 1000,
        ]);

        EtfMetric::factory()->create([
            'etf_id' => $etf->id,
            'performance_range_type_id' => PerformanceRangeType::NINETY_DAY,
            'total_return_percentage' => '18.0000',
        ]);

        EtfMetric::factory()->create([
            'etf_id' => $etf->id,
            'performance_range_type_id' => PerformanceRangeType::MAX,
            'nav_erosion_percentage' => '-2.0000',
        ]);

        $response = $this->getJson(
            "/api/portfolio-compare/{$portfolio->id}?metric=price&range=30d"
        );

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
        ]);

        $response->assertJsonPath('data.portfolio.id', $portfolio->id);
        $response->assertJsonPath('data.portfolio.name', 'Main Portfolio');
        $response->assertJsonPath('data.selected.metric', 'price');
        $response->assertJsonPath('data.selected.range', '30d');
        $response->assertJsonPath('data.summary.compared_etfs_count', 1);
        $response->assertJsonPath('data.table_rows.0.symbol', 'NVII');

        $this->assertEquals(
            30.0,
            $response->json('data.table_rows.0.latest_price')
        );
    }

    public function test_authenticated_user_gets_empty_compare_payload_for_portfolio_with_no_holdings(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Empty Portfolio',
            'status_id' => Status::ACTIVE,
        ]);

        $response = $this->getJson("/api/portfolio-compare/{$portfolio->id}");

        $response->assertStatus(200);

        $response->assertJsonPath('data.summary.compared_etfs_count', 0);

        $this->assertSame([], $response->json('data.table_rows'));
        $this->assertSame([], $response->json('data.chart_rows'));
    }

    public function test_guest_cannot_get_portfolio_compare_data(): void
    {
        $response = $this->getJson('/api/portfolio-compare/1');

        $response->assertStatus(401);
    }

    public function test_user_cannot_get_compare_data_for_another_users_portfolio(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = Portfolio::factory()->create([
            'user_id' => $otherUser->id,
            'status_id' => Status::ACTIVE,
        ]);

        $response = $this->getJson("/api/portfolio-compare/{$portfolio->id}");

        $response->assertStatus(500);

        $response->assertJson([
            'success' => false,
            'message' => 'Oops, something went wrong. Please try again later.',
        ]);
    }
}

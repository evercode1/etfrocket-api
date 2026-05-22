<?php

namespace Tests\Feature\Holdings;

use App\Models\Etf;
use App\Models\EtfDividendHistory;
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

class PortfolioHoldingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-05-20'));

        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('etf_dividend_histories')->truncate();
        DB::table('etf_metrics')->truncate();
        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('etf_dividend_histories')->truncate();
        DB::table('etf_metrics')->truncate();
        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();
        DB::table('users')->truncate();

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_authenticated_user_can_get_portfolio_holdings_analysis(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = $this->createPortfolio($user->id, 'Main Portfolio');

        $etf = $this->createEtf('NVII');

        $this->createBuyTransaction($portfolio->id, $etf->id, 10, 20);

        $this->createPrice($etf->id, 25);

        $this->createMonthlyDividend($etf->id, '2026-05-01', 1.00);

        $this->createMetric($etf->id, PerformanceRangeType::THIRTY_DAY, [
            'aum_change_percentage' => '12.5000',
        ]);

        $this->createMetric($etf->id, PerformanceRangeType::MAX, [
            'nav_erosion_percentage' => '-2.0000',
        ]);

        $response = $this->getJson("/api/portfolio-holdings/{$portfolio->id}");

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
        ]);

        $response->assertJsonPath('data.portfolio.id', $portfolio->id);
        $response->assertJsonPath('data.portfolio.name', 'Main Portfolio');

        $response->assertJsonPath('data.summary.holdings_count', 1);
        $response->assertJsonPath('data.summary.market_value', 250);
        $response->assertJsonPath('data.summary.cost_basis', 200);
        $response->assertJsonPath('data.summary.unrealized_gain_loss', 50);
        $response->assertJsonPath('data.summary.unrealized_gain_loss_percentage', 25);
        $response->assertJsonPath('data.summary.monthly_income', 10);
        $response->assertJsonPath('data.summary.yield_on_cost_percentage', 60);

        $response->assertJsonPath('data.holdings.0.symbol', 'NVII');
        $response->assertJsonPath('data.holdings.0.shares', 10);
        $response->assertJsonPath('data.holdings.0.average_cost', 20);
        $response->assertJsonPath('data.holdings.0.current_price', 25);
        $response->assertJsonPath('data.holdings.0.market_value', 250);
        $response->assertJsonPath('data.holdings.0.cost_basis', 200);
        $response->assertJsonPath('data.holdings.0.unrealized_gain_loss', 50);
        $response->assertJsonPath('data.holdings.0.unrealized_gain_loss_percentage', 25);
        $response->assertJsonPath('data.holdings.0.estimated_monthly_income', 10);
        $response->assertJsonPath('data.holdings.0.yield_on_cost_percentage', 60);
        $response->assertJsonPath('data.holdings.0.allocation_percentage', 100);
        $response->assertJsonPath('data.holdings.0.income_allocation_percentage', 100);
        $response->assertJsonPath('data.holdings.0.nav_change_percentage', -2);
        $response->assertJsonPath('data.holdings.0.nav_health', 'Stable');
        $response->assertJsonPath('data.holdings.0.aum_flow_percentage', 12.5);

        $response->assertJsonPath('data.insights.largest_position.symbol', 'NVII');
        $response->assertJsonPath('data.insights.largest_position.value', 100);

        $response->assertJsonPath('data.insights.top_income_driver.symbol', 'NVII');
        $response->assertJsonPath('data.insights.top_income_driver.value', 100);

        $response->assertJsonPath('data.insights.highest_gain.symbol', 'NVII');
        $response->assertJsonPath('data.insights.highest_gain.value', 50);
    }

    public function test_authenticated_user_gets_empty_payload_for_portfolio_with_no_holdings(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = $this->createPortfolio($user->id, 'Empty Portfolio');

        $response = $this->getJson("/api/portfolio-holdings/{$portfolio->id}");

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
        ]);

        $response->assertJsonPath('data.portfolio.id', $portfolio->id);
        $response->assertJsonPath('data.portfolio.name', 'Empty Portfolio');

        $response->assertJsonPath('data.summary.holdings_count', 0);
        $response->assertJsonPath('data.summary.market_value', 0);
        $response->assertJsonPath('data.summary.cost_basis', 0);
        $response->assertJsonPath('data.summary.monthly_income', 0);
        $response->assertJsonPath('data.summary.unrealized_gain_loss', 0);
        $response->assertJsonPath('data.summary.unrealized_gain_loss_percentage', null);
        $response->assertJsonPath('data.summary.yield_on_cost_percentage', null);

        $response->assertJsonPath('data.insights.largest_position', null);
        $response->assertJsonPath('data.insights.top_income_driver', null);
        $response->assertJsonPath('data.insights.highest_gain', null);

        $this->assertSame([], $response->json('data.holdings'));
    }

    public function test_guest_cannot_get_portfolio_holdings_analysis(): void
    {
        $response = $this->getJson('/api/portfolio-holdings/1');

        $response->assertStatus(401);
    }

    public function test_user_cannot_get_holdings_for_another_users_portfolio(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $portfolio = $this->createPortfolio($otherUser->id, 'Other Portfolio');

        $response = $this->getJson("/api/portfolio-holdings/{$portfolio->id}");

        $response->assertStatus(500);

        $response->assertJson([
            'success' => false,
            'message' => 'Unable to load holdings at this time.',
        ]);
    }

    private function createPortfolio(int $userId, string $name): Portfolio
    {
        return Portfolio::factory()->create([
            'user_id' => $userId,
            'portfolio_name' => $name,
            'status_id' => Status::ACTIVE,
        ]);
    }

    private function createEtf(string $symbol): Etf
    {
        return Etf::factory()->create([
            'symbol' => $symbol,
            'fund_name' => "{$symbol} Test ETF",
            'status_id' => Status::ACTIVE,
        ]);
    }

    private function createBuyTransaction(
        int $portfolioId,
        int $etfId,
        float $shares,
        float $price
    ): PortfolioTransaction {
        return PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolioId,
            'etf_id' => $etfId,
            'transaction_type_id' => 1,
            'shares' => $shares,
            'price_per_share' => $price,
            'transaction_date' => '2026-01-01',
        ]);
    }

    private function createPrice(int $etfId, float $price): EtfPriceHistory
    {
        return EtfPriceHistory::factory()->create([
            'etf_id' => $etfId,
            'price_date' => '2026-05-20',
            'close_price' => $price,
            'volume' => 1000,
        ]);
    }

    private function createMonthlyDividend(
        int $etfId,
        string $date,
        float $amount
    ): EtfDividendHistory {
        return EtfDividendHistory::factory()->create([
            'etf_id' => $etfId,
            'dividend_amount' => $amount,
            'ex_dividend_date' => $date,
            'payment_date' => $date,
            'data_source_id' => 1,
        ]);
    }

    private function createMetric(
        int $etfId,
        int $rangeTypeId,
        array $overrides = []
    ): EtfMetric {
        return EtfMetric::factory()->create(array_merge([
            'etf_id' => $etfId,
            'performance_range_type_id' => $rangeTypeId,
            'start_date' => '2026-01-01',
            'end_date' => '2026-05-20',
            'price_change_percentage' => '0.0000',
            'aum_change_percentage' => null,
            'nav_erosion_percentage' => null,
            'total_return_percentage' => '0.0000',
            'dividends_paid' => '0.0000',
            'dividend_count' => 0,
        ], $overrides));
    }
}

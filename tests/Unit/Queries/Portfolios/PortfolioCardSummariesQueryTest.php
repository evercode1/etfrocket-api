<?php

namespace Tests\Unit\Queries\Portfolios;

use App\Models\PerformanceRangeType;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Security;
use App\Models\SecurityDividendHistory;
use App\Models\SecurityMetric;
use App\Models\SecurityPriceHistory;
use App\Models\Status;
use App\Models\User;
use App\Queries\Portfolios\PortfolioCardSummariesQuery;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortfolioCardSummariesQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('security_metrics')->truncate();
        DB::table('security_dividend_histories')->truncate();
        DB::table('security_price_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('security_metrics')->truncate();
        DB::table('security_dividend_histories')->truncate();
        DB::table('security_price_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_portfolio_card_summaries_for_user(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Income Rocket',
            'status_id' => Status::ACTIVE,
            'is_default' => 1,
        ]);

        $security = Security::factory()->create([
            'symbol' => 'NVII',
            'fund_name' => 'NVII Test Security',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'transaction_type_id' => 1,
            'shares' => 10,
            'price_per_share' => 20,
            'transaction_date' => '2026-01-01',
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2026-05-15',
            'close_price' => '30.0000',
            'volume' => 100000,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'dividend_amount' => '0.6000',
            'ex_dividend_date' => '2026-05-01',
            'payment_date' => '2026-05-02',
            'data_source_id' => 1,
        ]);

        SecurityMetric::factory()->create([
            'security_id' => $security->id,
            'performance_range_type_id' => PerformanceRangeType::MAX,
            'nav_erosion_percentage' => '1.0000',
        ]);

        $results = app(PortfolioCardSummariesQuery::class)->getData($user->id);

        $this->assertCount(1, $results);

        $this->assertSame($portfolio->id, $results[0]['id']);
        $this->assertSame('Income Rocket', $results[0]['portfolio_name']);
        $this->assertTrue($results[0]['is_default']);
        $this->assertSame(300.0, $results[0]['portfolio_value']);

        $this->assertSame(6.0, $results[0]['monthly_income']);

        $this->assertSame('Stable', $results[0]['nav_health']);
        $this->assertSame(1, $results[0]['holdings_count']);
    }

    public function test_it_returns_multiple_portfolios_ordered_by_default_then_name(): void
    {
        $user = User::factory()->create();

        $zPortfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Z Portfolio',
            'status_id' => Status::ACTIVE,
            'is_default' => 0,
        ]);

        $defaultPortfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Default Portfolio',
            'status_id' => Status::ACTIVE,
            'is_default' => 1,
        ]);

        $aPortfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'A Portfolio',
            'status_id' => Status::ACTIVE,
            'is_default' => 0,
        ]);

        $results = app(PortfolioCardSummariesQuery::class)->getData($user->id);

        $this->assertCount(3, $results);

        $this->assertSame($defaultPortfolio->id, $results[0]['id']);
        $this->assertSame($aPortfolio->id, $results[1]['id']);
        $this->assertSame($zPortfolio->id, $results[2]['id']);
    }

    public function test_it_does_not_return_other_users_portfolios(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        $userPortfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'User Portfolio',
            'status_id' => Status::ACTIVE,
            'is_default' => 1,
        ]);

        Portfolio::factory()->create([
            'user_id' => $otherUser->id,
            'portfolio_name' => 'Other User Portfolio',
            'status_id' => Status::ACTIVE,
            'is_default' => 1,
        ]);

        $results = app(PortfolioCardSummariesQuery::class)->getData($user->id);

        $this->assertCount(1, $results);
        $this->assertSame($userPortfolio->id, $results[0]['id']);
        $this->assertSame('User Portfolio', $results[0]['portfolio_name']);
    }

    public function test_it_returns_empty_array_when_user_has_no_portfolios(): void
    {
        $user = User::factory()->create();

        $results = app(PortfolioCardSummariesQuery::class)->getData($user->id);

        $this->assertSame([], $results);
    }

    public function test_it_returns_zero_summary_for_portfolio_with_no_holdings(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Empty Portfolio',
            'status_id' => Status::ACTIVE,
            'is_default' => 1,
        ]);

        $results = app(PortfolioCardSummariesQuery::class)->getData($user->id);

        $this->assertCount(1, $results);

        $this->assertSame($portfolio->id, $results[0]['id']);
        $this->assertSame('Empty Portfolio', $results[0]['portfolio_name']);
        $this->assertTrue($results[0]['is_default']);
        $this->assertSame(0, $results[0]['portfolio_value']);
        $this->assertSame(0, $results[0]['monthly_income']);
        $this->assertSame('No Holdings', $results[0]['nav_health']);
        $this->assertSame(0, $results[0]['holdings_count']);
    }
}

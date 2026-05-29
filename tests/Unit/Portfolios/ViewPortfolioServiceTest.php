<?php

namespace Tests\Unit\Portfolios;

use App\Models\PerformanceRangeType;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Security;
use App\Models\SecurityDividendHistory;
use App\Models\SecurityMetric;
use App\Models\SecurityPriceHistory;
use App\Models\Status;
use App\Models\User;
use App\Services\Portfolios\ViewPortfolioService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ViewPortfolioServiceTest extends TestCase
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
        DB::table('security_details')->truncate();
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
        DB::table('security_details')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_portfolio_detail_payload(): void
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
            'total_return_percentage' => '12.5000',
            'nav_erosion_percentage' => '1.0000',
        ]);

        $data = app(ViewPortfolioService::class)->getData($user->id, $portfolio->id);

        $this->assertSame($portfolio->id, $data['id']);
        $this->assertSame('Income Rocket', $data['portfolio_name']);
        $this->assertTrue($data['is_default']);
        $this->assertSame(Status::ACTIVE, $data['status_id']);

        $this->assertSame(300.0, $data['portfolio_value']);
        $this->assertSame(200.0, $data['cost_basis']);
        $this->assertSame(100.0, $data['unrealized_gain_loss']);
        $this->assertSame(50.0, $data['total_return_percentage']);
        $this->assertSame(6.0, $data['monthly_income']);
        $this->assertSame('Stable', $data['nav_health']);

        $this->assertCount(1, $data['holdings']);

        $this->assertSame($security->id, $data['holdings'][0]['security_id']);
        $this->assertSame('NVII', $data['holdings'][0]['symbol']);
        $this->assertSame(10.0, $data['holdings'][0]['shares']);
        $this->assertSame(200.0, $data['holdings'][0]['cost_basis']);
        $this->assertSame(30.0, $data['holdings'][0]['latest_price']);
        $this->assertSame(300.0, $data['holdings'][0]['market_value']);
        $this->assertSame(6.0, $data['holdings'][0]['estimated_monthly_income']);
        $this->assertSame(100.0, $data['holdings'][0]['allocation_percentage']);
    }

    public function test_it_returns_multiple_holdings_with_allocation_percentages(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Allocation Portfolio',
            'status_id' => Status::ACTIVE,
            'is_default' => 0,
        ]);

        $firstSecurity = Security::factory()->create([
            'symbol' => 'NVII',

        ]);

        $secondSecurity = Security::factory()->create([
            'symbol' => 'AMDY',

        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $firstSecurity->id,
            'transaction_type_id' => 1,
            'shares' => 10,
            'price_per_share' => 20,
            'transaction_date' => '2026-01-01',
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $secondSecurity->id,
            'transaction_type_id' => 1,
            'shares' => 20,
            'price_per_share' => 10,
            'transaction_date' => '2026-01-01',
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $firstSecurity->id,
            'price_date' => '2026-05-15',
            'close_price' => '30.0000',
            'volume' => 100000,
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $secondSecurity->id,
            'price_date' => '2026-05-15',
            'close_price' => '10.0000',
            'volume' => 100000,
        ]);

        $data = app(ViewPortfolioService::class)->getData($user->id, $portfolio->id);

        $this->assertSame(500.0, $data['portfolio_value']);
        $this->assertCount(2, $data['holdings']);

        $firstHolding = collect($data['holdings'])
            ->firstWhere('symbol', 'NVII');

        $secondHolding = collect($data['holdings'])
            ->firstWhere('symbol', 'AMDY');

        $this->assertSame(300.0, $firstHolding['market_value']);
        $this->assertSame(60.0, $firstHolding['allocation_percentage']);

        $this->assertSame(200.0, $secondHolding['market_value']);
        $this->assertSame(40.0, $secondHolding['allocation_percentage']);
    }

    public function test_it_returns_empty_holdings_for_portfolio_with_no_transactions(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Empty Portfolio',
            'status_id' => Status::ACTIVE,
            'is_default' => 1,
        ]);

        $data = app(ViewPortfolioService::class)->getData($user->id, $portfolio->id);

        $this->assertSame($portfolio->id, $data['id']);
        $this->assertSame('Empty Portfolio', $data['portfolio_name']);
        $this->assertTrue($data['is_default']);

        $this->assertSame(0, $data['portfolio_value']);
        $this->assertSame(0, $data['cost_basis']);
        $this->assertSame(0, $data['unrealized_gain_loss']);
        $this->assertNull($data['total_return_percentage']);
        $this->assertSame(0, $data['monthly_income']);
        $this->assertSame('No Holdings', $data['nav_health']);
        $this->assertSame([], $data['holdings']);
    }

    public function test_it_throws_exception_when_portfolio_does_not_belong_to_user(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        $otherPortfolio = Portfolio::factory()->create([
            'user_id' => $otherUser->id,
            'portfolio_name' => 'Other User Portfolio',
            'status_id' => Status::ACTIVE,
            'is_default' => 1,
        ]);

        $this->expectException(ModelNotFoundException::class);

        app(ViewPortfolioService::class)->getData($user->id, $otherPortfolio->id);
    }

    public function test_it_throws_exception_when_portfolio_does_not_exist(): void
    {
        $user = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        app(ViewPortfolioService::class)->getData($user->id, 999999);
    }
}

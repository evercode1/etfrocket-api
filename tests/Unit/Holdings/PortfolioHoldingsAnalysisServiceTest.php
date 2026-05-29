<?php

namespace Tests\Unit\Holdings;

use App\Models\PerformanceRangeType;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Security;
use App\Models\SecurityDividendHistory;
use App\Models\SecurityMetric;
use App\Models\SecurityPriceHistory;
use App\Models\Status;
use App\Models\User;
use App\Services\Holdings\PortfolioHoldingsAnalysisService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortfolioHoldingsAnalysisServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-05-20'));

        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('security_dividend_histories')->truncate();
        DB::table('security_metrics')->truncate();
        DB::table('security_price_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('security_dividend_histories')->truncate();
        DB::table('security_metrics')->truncate();
        DB::table('security_price_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('users')->truncate();

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_returns_holdings_analysis_for_portfolio(): void
    {
        $user = User::factory()->create();

        $portfolio = $this->createPortfolio($user->id, 'Main Portfolio');

        $nvi = $this->createSecurity('NVII');
        $chpy = $this->createSecurity('CHPY');

        $this->createBuyTransaction($portfolio->id, $nvi->id, 10, 20);
        $this->createBuyTransaction($portfolio->id, $chpy->id, 5, 30);

        $this->createPrice($nvi->id, 25);
        $this->createPrice($chpy->id, 40);

        $this->createMonthlyDividend($nvi->id, '2026-05-01', 1.00);
        $this->createMonthlyDividend($chpy->id, '2026-05-01', 2.00);

        $this->createMetric($nvi->id, PerformanceRangeType::THIRTY_DAY, [
            'aum_change_percentage' => '12.5000',
        ]);

        $this->createMetric($nvi->id, PerformanceRangeType::MAX, [
            'nav_erosion_percentage' => '-2.0000',
        ]);

        $this->createMetric($chpy->id, PerformanceRangeType::THIRTY_DAY, [
            'aum_change_percentage' => '-6.0000',
        ]);

        $this->createMetric($chpy->id, PerformanceRangeType::MAX, [
            'nav_erosion_percentage' => '-12.0000',
        ]);

        $data = app(PortfolioHoldingsAnalysisService::class)->getData(
            $user->id,
            $portfolio->id
        );

        $this->assertSame($portfolio->id, $data['portfolio']['id']);
        $this->assertSame('Main Portfolio', $data['portfolio']['name']);

        $this->assertSame(2, $data['summary']['holdings_count']);
        $this->assertSame(450.0, $data['summary']['market_value']);
        $this->assertSame(350.0, $data['summary']['cost_basis']);
        $this->assertSame(100.0, $data['summary']['unrealized_gain_loss']);
        $this->assertSame(28.5714, $data['summary']['unrealized_gain_loss_percentage']);
        $this->assertSame(20.0, $data['summary']['monthly_income']);
        $this->assertSame(68.5714, $data['summary']['yield_on_cost_percentage']);

        $this->assertCount(2, $data['holdings']);

        $firstRow = $data['holdings'][0];

        $this->assertSame('NVII', $firstRow['symbol']);
        $this->assertSame(10.0, $firstRow['shares']);
        $this->assertSame(20.0, $firstRow['average_cost']);
        $this->assertSame(25.0, $firstRow['current_price']);
        $this->assertSame(250.0, $firstRow['market_value']);
        $this->assertSame(200.0, $firstRow['cost_basis']);
        $this->assertSame(50.0, $firstRow['unrealized_gain_loss']);
        $this->assertSame(25.0, $firstRow['unrealized_gain_loss_percentage']);
        $this->assertSame(10.0, $firstRow['estimated_monthly_income']);
        $this->assertSame(60.0, $firstRow['yield_on_cost_percentage']);
        $this->assertSame(55.5556, $firstRow['allocation_percentage']);
        $this->assertSame(50.0, $firstRow['income_allocation_percentage']);
        $this->assertSame(-2.0, $firstRow['nav_change_percentage']);
        $this->assertSame('Stable', $firstRow['nav_health']);
        $this->assertSame(12.5, $firstRow['aum_flow_percentage']);

        $secondRow = $data['holdings'][1];

        $this->assertSame('CHPY', $secondRow['symbol']);
        $this->assertSame(200.0, $secondRow['market_value']);
        $this->assertSame(150.0, $secondRow['cost_basis']);
        $this->assertSame(50.0, $secondRow['unrealized_gain_loss']);
        $this->assertSame(33.3333, $secondRow['unrealized_gain_loss_percentage']);
        $this->assertSame(10.0, $secondRow['estimated_monthly_income']);
        $this->assertSame(80.0, $secondRow['yield_on_cost_percentage']);
        $this->assertSame(44.4444, $secondRow['allocation_percentage']);
        $this->assertSame(50.0, $secondRow['income_allocation_percentage']);
        $this->assertSame(-12.0, $secondRow['nav_change_percentage']);
        $this->assertSame('Watch', $secondRow['nav_health']);
        $this->assertSame(-6.0, $secondRow['aum_flow_percentage']);

        $this->assertSame('NVII', $data['insights']['largest_position']['symbol']);
        $this->assertSame(55.5556, $data['insights']['largest_position']['value']);

        $this->assertSame('NVII', $data['insights']['top_income_driver']['symbol']);
        $this->assertSame(50.0, $data['insights']['top_income_driver']['value']);

        $this->assertSame('NVII', $data['insights']['highest_gain']['symbol']);
        $this->assertSame(50.0, $data['insights']['highest_gain']['value']);
    }

    public function test_it_returns_empty_payload_when_portfolio_has_no_holdings(): void
    {
        $user = User::factory()->create();

        $portfolio = $this->createPortfolio($user->id, 'Empty Portfolio');

        $data = app(PortfolioHoldingsAnalysisService::class)->getData(
            $user->id,
            $portfolio->id
        );

        $this->assertSame($portfolio->id, $data['portfolio']['id']);
        $this->assertSame('Empty Portfolio', $data['portfolio']['name']);

        $this->assertSame(0, $data['summary']['holdings_count']);
        $this->assertSame(0, $data['summary']['market_value']);
        $this->assertSame(0, $data['summary']['cost_basis']);
        $this->assertSame(0, $data['summary']['monthly_income']);
        $this->assertSame(0, $data['summary']['unrealized_gain_loss']);
        $this->assertNull($data['summary']['unrealized_gain_loss_percentage']);
        $this->assertNull($data['summary']['yield_on_cost_percentage']);

        $this->assertNull($data['insights']['largest_position']);
        $this->assertNull($data['insights']['top_income_driver']);
        $this->assertNull($data['insights']['highest_gain']);

        $this->assertSame([], $data['holdings']);
    }

    public function test_it_excludes_fully_sold_positions(): void
    {
        $user = User::factory()->create();

        $portfolio = $this->createPortfolio($user->id, 'Main Portfolio');

        $heldSecurity = $this->createSecurity('HELD');
        $soldSecurity = $this->createSecurity('SOLD');

        $this->createBuyTransaction($portfolio->id, $heldSecurity->id, 10, 20);
        $this->createBuyTransaction($portfolio->id, $soldSecurity->id, 10, 20);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $soldSecurity->id,
            'transaction_type_id' => 2,
            'shares' => 10,
            'price_per_share' => 20,
            'transaction_date' => '2026-02-01',
        ]);

        $this->createPrice($heldSecurity->id, 25);
        $this->createPrice($soldSecurity->id, 50);

        $data = app(PortfolioHoldingsAnalysisService::class)->getData(
            $user->id,
            $portfolio->id
        );

        $this->assertSame(1, $data['summary']['holdings_count']);
        $this->assertCount(1, $data['holdings']);
        $this->assertSame('HELD', $data['holdings'][0]['symbol']);
    }

    public function test_it_handles_missing_price_and_metric_data(): void
    {
        $user = User::factory()->create();

        $portfolio = $this->createPortfolio($user->id, 'Main Portfolio');

        $security = $this->createSecurity('MISS');

        $this->createBuyTransaction($portfolio->id, $security->id, 10, 20);

        $data = app(PortfolioHoldingsAnalysisService::class)->getData(
            $user->id,
            $portfolio->id
        );

        $row = $data['holdings'][0];

        $this->assertSame('MISS', $row['symbol']);
        $this->assertNull($row['current_price']);
        $this->assertSame(0.0, $row['market_value']);
        $this->assertSame(200.0, $row['cost_basis']);
        $this->assertSame(-200.0, $row['unrealized_gain_loss']);
        $this->assertSame(-100.0, $row['unrealized_gain_loss_percentage']);
        $this->assertSame('Unknown', $row['nav_health']);
        $this->assertNull($row['nav_change_percentage']);
        $this->assertNull($row['aum_flow_percentage']);
    }

    public function test_it_throws_exception_when_portfolio_does_not_belong_to_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $portfolio = $this->createPortfolio($otherUser->id, 'Other Portfolio');

        $this->expectException(ModelNotFoundException::class);

        app(PortfolioHoldingsAnalysisService::class)->getData(
            $user->id,
            $portfolio->id
        );
    }

    private function createPortfolio(int $userId, string $name): Portfolio
    {
        return Portfolio::factory()->create([
            'user_id' => $userId,
            'portfolio_name' => $name,
            'status_id' => Status::ACTIVE,
        ]);
    }

    private function createSecurity(string $symbol): Security
    {
        return Security::factory()->create([
            'symbol' => $symbol,
            'status_id' => Status::ACTIVE,
        ]);
    }

    private function createBuyTransaction(
        int $portfolioId,
        int $securityId,
        float $shares,
        float $price
    ): PortfolioTransaction {
        return PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolioId,
            'security_id' => $securityId,
            'transaction_type_id' => 1,
            'shares' => $shares,
            'price_per_share' => $price,
            'transaction_date' => '2026-01-01',
        ]);
    }

    private function createPrice(int $securityId, float $price): SecurityPriceHistory
    {
        return SecurityPriceHistory::factory()->create([
            'security_id' => $securityId,
            'price_date' => '2026-05-20',
            'close_price' => $price,
            'volume' => 1000,
        ]);
    }

    private function createMonthlyDividend(
        int $securityId,
        string $date,
        float $amount
    ): SecurityDividendHistory {
        return SecurityDividendHistory::factory()->create([
            'security_id' => $securityId,
            'dividend_amount' => $amount,
            'ex_dividend_date' => $date,
            'payment_date' => $date,
            'data_source_id' => 1,
        ]);
    }

    private function createMetric(
        int $securityId,
        int $rangeTypeId,
        array $overrides = []
    ): SecurityMetric {
        return SecurityMetric::factory()->create(array_merge([
            'security_id' => $securityId,
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

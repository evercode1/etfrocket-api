<?php

namespace Tests\Unit\Comparisons;

use App\Models\PerformanceRangeType;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Security;
use App\Models\SecurityMetric;
use App\Models\SecurityPriceHistory;
use App\Models\Status;
use App\Models\User;
use App\Services\Comparisons\PortfolioCompareService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortfolioCompareServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-05-20'));

        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
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
        DB::table('security_metrics')->truncate();
        DB::table('security_price_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('users')->truncate();

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_returns_portfolio_comparison_data(): void
    {
        $user = User::factory()->create();

        $portfolio = $this->createPortfolio($user->id, 'Main Portfolio');

        $security = $this->createSecurity('NVII');

        $this->createBuyTransaction($portfolio->id, $security->id, 10, 25);

        SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2026-05-01',
            'close_price' => '25.0000',
            'volume' => 1000,
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $security->id,
            'price_date' => '2026-05-20',
            'close_price' => '30.0000',
            'volume' => 1000,
        ]);

        $this->createMetric($security->id, PerformanceRangeType::THIRTY_DAY, [
            'price_change_percentage' => '20.0000',
            'aum_change_percentage' => '12.0000',
        ]);

        $this->createMetric($security->id, PerformanceRangeType::NINETY_DAY, [
            'total_return_percentage' => '18.0000',
        ]);

        $this->createMetric($security->id, PerformanceRangeType::MAX, [
            'nav_erosion_percentage' => '-2.0000',
            'total_return_percentage' => '25.0000',
        ]);

        $data = app(PortfolioCompareService::class)->getData(
            $user->id,
            $portfolio->id,
            [
                'metric' => 'price',
                'range' => '30d',
            ]
        );

        $this->assertSame($portfolio->id, $data['portfolio']['id']);
        $this->assertSame('Main Portfolio', $data['portfolio']['name']);

        $this->assertSame('price', $data['selected']['metric']);
        $this->assertSame('30d', $data['selected']['range']);

        $this->assertSame(1, $data['summary']['compared_securities_count']);
        $this->assertSame('NVII', $data['summary']['best_total_return_symbol']);
        $this->assertSame(18.0, $data['summary']['best_total_return_percentage']);
        $this->assertSame('NVII', $data['summary']['strongest_nav_symbol']);
        $this->assertSame(-2.0, $data['summary']['strongest_nav_change_percentage']);

        $this->assertCount(1, $data['table_rows']);

        $row = $data['table_rows'][0];

        $this->assertSame($security->id, $row['security_id']);
        $this->assertSame('NVII', $row['symbol']);
        $this->assertSame(10.0, $row['shares']);
        $this->assertSame(250.0, $row['cost_basis']);
        $this->assertSame(30.0, $row['latest_price']);
        $this->assertSame(300.0, $row['market_value']);
        $this->assertSame(20.0, $row['price_change_percentage_30_day']);
        $this->assertSame(12.0, $row['aum_change_percentage_30_day']);
        $this->assertSame(-2.0, $row['nav_change_percentage_max']);
        $this->assertSame(18.0, $row['total_return_percentage_90_day']);
        $this->assertSame('Stable', $row['nav_health']);

        $this->assertCount(2, $data['chart_rows']);
        $this->assertSame('May 01', $data['chart_rows'][0]['date']);
        $this->assertSame(25.0, $data['chart_rows'][0]['NVII']);
        $this->assertSame('May 20', $data['chart_rows'][1]['date']);
        $this->assertSame(30.0, $data['chart_rows'][1]['NVII']);
    }

    public function test_it_returns_empty_payload_when_portfolio_has_no_holdings(): void
    {
        $user = User::factory()->create();

        $portfolio = $this->createPortfolio($user->id, 'Empty Portfolio');

        $data = app(PortfolioCompareService::class)->getData(
            $user->id,
            $portfolio->id
        );

        $this->assertSame($portfolio->id, $data['portfolio']['id']);
        $this->assertSame(0, $data['summary']['compared_securities_count']);
        $this->assertSame([], $data['table_rows']);
        $this->assertSame([], $data['chart_rows']);
    }

    public function test_it_limits_comparison_to_top_holdings_by_market_value(): void
    {
        config([
            'security_comparison.defaults.max_securities' => 2,
        ]);

        $user = User::factory()->create();

        $portfolio = $this->createPortfolio($user->id, 'Main Portfolio');

        $smallSecurity = $this->createSecurity('SMALL');
        $mediumSecurity = $this->createSecurity('MED');
        $largeSecurity = $this->createSecurity('LARGE');

        $this->createBuyTransaction($portfolio->id, $smallSecurity->id, 10, 10);
        $this->createBuyTransaction($portfolio->id, $mediumSecurity->id, 10, 10);
        $this->createBuyTransaction($portfolio->id, $largeSecurity->id, 10, 10);

        SecurityPriceHistory::factory()->create([
            'security_id' => $smallSecurity->id,
            'price_date' => '2026-05-20',
            'close_price' => '10.0000',
            'volume' => 1000,
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $mediumSecurity->id,
            'price_date' => '2026-05-20',
            'close_price' => '25.0000',
            'volume' => 1000,
        ]);

        SecurityPriceHistory::factory()->create([
            'security_id' => $largeSecurity->id,
            'price_date' => '2026-05-20',
            'close_price' => '50.0000',
            'volume' => 1000,
        ]);

        $data = app(PortfolioCompareService::class)->getData(
            $user->id,
            $portfolio->id,
            [
                'metric' => 'price',
                'range' => '30d',
            ]
        );

        $this->assertSame(3, $data['comparison_limit']['total_holdings_count']);
        $this->assertSame(2, $data['comparison_limit']['included_holdings_count']);
        $this->assertSame(2, $data['comparison_limit']['max_securities']);
        $this->assertSame(
            'Top holdings by current market value',
            $data['comparison_limit']['selection_method']
        );

        $this->assertCount(2, $data['table_rows']);

        $this->assertSame('LARGE', $data['table_rows'][0]['symbol']);
        $this->assertSame(500.0, $data['table_rows'][0]['market_value']);

        $this->assertSame('MED', $data['table_rows'][1]['symbol']);
        $this->assertSame(250.0, $data['table_rows'][1]['market_value']);

        $symbols = collect($data['table_rows'])->pluck('symbol')->toArray();

        $this->assertNotContains('SMALL', $symbols);
    }

    public function test_empty_payload_includes_comparison_limit_metadata(): void
    {
        config([
            'etf_comparison.defaults.max_securities' => 5,
        ]);

        $user = User::factory()->create();

        $portfolio = $this->createPortfolio($user->id, 'Empty Portfolio');

        $data = app(PortfolioCompareService::class)->getData(
            $user->id,
            $portfolio->id
        );

        $this->assertSame(5, $data['comparison_limit']['max_securities']);
        $this->assertSame(0, $data['comparison_limit']['total_holdings_count']);
        $this->assertSame(0, $data['comparison_limit']['included_holdings_count']);
        $this->assertSame(
            'Top holdings by current market value',
            $data['comparison_limit']['selection_method']
        );
    }

    public function test_it_throws_exception_when_portfolio_does_not_belong_to_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $portfolio = $this->createPortfolio($otherUser->id, 'Other Portfolio');

        $this->expectException(ModelNotFoundException::class);

        app(PortfolioCompareService::class)->getData(
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
            'aum_change_percentage' => '0.0000',
            'nav_erosion_percentage' => '0.0000',
            'total_return_percentage' => '0.0000',
            'dividends_paid' => '0.0000',
            'dividend_count' => 0,
        ], $overrides));
    }
}

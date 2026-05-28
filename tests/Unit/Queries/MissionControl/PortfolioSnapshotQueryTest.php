<?php

namespace Tests\Unit\Queries\MissionControl;

use App\Models\Etf;
use App\Models\EtfDividendHistory;
use App\Models\EtfMetric;
use App\Models\EtfPriceHistory;
use App\Models\PerformanceRangeType;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Status;
use App\Models\User;
use App\Queries\MissionControl\PortfolioSnapshotQuery;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortfolioSnapshotQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('etf_metrics')->truncate();
        DB::table('etf_dividend_histories')->truncate();
        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('etf_metrics')->truncate();
        DB::table('etf_dividend_histories')->truncate();
        DB::table('etf_price_histories')->truncate();
        DB::table('etfs')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_portfolio_snapshot_for_portfolio_with_holdings(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Income Rocket',
            'status_id' => Status::ACTIVE,
            'is_default' => 1,
        ]);

        $etf = Etf::factory()->create([
            'symbol' => 'NVII',
            'fund_name' => 'NVII Test ETF',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $etf->id,
            'transaction_type_id' => 1,
            'shares' => 10,
            'price_per_share' => 25,
            'transaction_date' => '2026-01-01',
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $etf->id,
            'transaction_type_id' => 1,
            'shares' => 5,
            'price_per_share' => 30,
            'transaction_date' => '2026-02-01',
        ]);

        EtfPriceHistory::factory()->create([
            'etf_id' => $etf->id,
            'price_date' => '2026-05-15',
            'close_price' => '40.0000',
            'volume' => 100000,
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '0.2500',
            'ex_dividend_date' => '2026-05-01',
            'payment_date' => '2026-05-02',
            'data_source_id' => 1,
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '0.3000',
            'ex_dividend_date' => '2026-04-01',
            'payment_date' => '2026-04-02',
            'data_source_id' => 1,
        ]);

        EtfMetric::factory()->create([
            'etf_id' => $etf->id,
            'performance_range_type_id' => PerformanceRangeType::MAX,
            'total_return_percentage' => '12.5000',
            'nav_erosion_percentage' => '1.2500',
        ]);

        $snapshot = app(PortfolioSnapshotQuery::class)->getData($portfolio->id);

        $this->assertSame($portfolio->id, $snapshot['portfolio_id']);
        $this->assertSame('Income Rocket', $snapshot['portfolio_name']);

        $this->assertSame(600.0, $snapshot['portfolio_value']);
        $this->assertSame(400.0, $snapshot['cost_basis']);
        $this->assertSame(200.0, $snapshot['unrealized_gain_loss']);
        $this->assertSame(50.0, $snapshot['total_return_percentage']);

        $this->assertSame(4.125, $snapshot['monthly_income']);

        $this->assertSame('Stable', $snapshot['nav_health']);

        $this->assertSame(1, $snapshot['holdings_count']);
        $this->assertTrue($snapshot['has_holdings']);

        $this->assertCount(1, $snapshot['holdings']);

        $this->assertSame($etf->id, $snapshot['holdings'][0]['etf_id']);
        $this->assertSame('NVII', $snapshot['holdings'][0]['symbol']);
        $this->assertSame('NVII Test ETF', $snapshot['holdings'][0]['fund_name']);
        $this->assertSame(15.0, $snapshot['holdings'][0]['shares']);
        $this->assertSame(400.0, $snapshot['holdings'][0]['cost_basis']);
        $this->assertSame(40.0, $snapshot['holdings'][0]['latest_price']);
        $this->assertSame(600.0, $snapshot['holdings'][0]['market_value']);
        $this->assertSame(4.125, $snapshot['holdings'][0]['estimated_monthly_income']);
        $this->assertEquals('12.5000', $snapshot['holdings'][0]['total_return_percentage']);
        $this->assertEquals('1.2500', $snapshot['holdings'][0]['nav_erosion_percentage']);
        $this->assertSame(2, $snapshot['holdings'][0]['distribution_frequency_id']);
        $this->assertCount(12, $snapshot['income_projection']);

        $this->assertSame(
            [
                'month' => now()->startOfMonth()->format('M'),
                'income' => 4.13,
            ],
            $snapshot['income_projection'][0]
        );

        $this->assertGreaterThan(
            $snapshot['income_projection'][0]['income'],
            $snapshot['income_projection'][11]['income']
        );
    }

    public function test_it_returns_zero_snapshot_for_portfolio_with_no_holdings(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Empty Portfolio',
            'status_id' => Status::ACTIVE,
            'is_default' => 1,
        ]);

        $snapshot = app(PortfolioSnapshotQuery::class)->getData($portfolio->id);

        $this->assertSame($portfolio->id, $snapshot['portfolio_id']);
        $this->assertSame('Empty Portfolio', $snapshot['portfolio_name']);
        $this->assertSame(0, $snapshot['portfolio_value']);
        $this->assertSame(0, $snapshot['cost_basis']);
        $this->assertSame(0, $snapshot['unrealized_gain_loss']);
        $this->assertNull($snapshot['total_return_percentage']);
        $this->assertSame(0, $snapshot['monthly_income']);
        $this->assertSame('No Holdings', $snapshot['nav_health']);
        $this->assertSame(0, $snapshot['holdings_count']);
        $this->assertFalse($snapshot['has_holdings']);
        $this->assertSame([], $snapshot['holdings']);
        $this->assertSame([], $snapshot['income_projection']);
    }

    public function test_it_returns_null_when_portfolio_does_not_exist(): void
    {
        $snapshot = app(PortfolioSnapshotQuery::class)->getData(999999);

        $this->assertNull($snapshot);
    }

    public function test_it_groups_multiple_transactions_for_same_etf(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Grouped Portfolio',
            'status_id' => Status::ACTIVE,
        ]);

        $etf = Etf::factory()->create([
            'symbol' => 'AMDY',
            'fund_name' => 'AMDY Test ETF',
            'status_id' => Status::ACTIVE,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $etf->id,
            'transaction_type_id' => 1,
            'shares' => 2,
            'price_per_share' => 20,
            'transaction_date' => '2026-01-01',
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $etf->id,
            'transaction_type_id' => 1,
            'shares' => 3,
            'price_per_share' => 30,
            'transaction_date' => '2026-02-01',
        ]);

        EtfPriceHistory::factory()->create([
            'etf_id' => $etf->id,
            'price_date' => '2026-05-15',
            'close_price' => '40.0000',
            'volume' => 100000,
        ]);

        $snapshot = app(PortfolioSnapshotQuery::class)->getData($portfolio->id);

        $this->assertCount(1, $snapshot['holdings']);
        $this->assertSame(5.0, $snapshot['holdings'][0]['shares']);
        $this->assertSame(130.0, $snapshot['holdings'][0]['cost_basis']);
        $this->assertSame(200.0, $snapshot['holdings'][0]['market_value']);
    }

    public function test_it_sets_nav_health_to_watch_when_nav_erosion_is_severe(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'portfolio_name' => 'Risk Portfolio',
            'status_id' => Status::ACTIVE,
        ]);

        $etf = Etf::factory()->create([
            'symbol' => 'GOOY',
            'fund_name' => 'GOOY Test ETF',
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
            'price_date' => '2026-05-15',
            'close_price' => '20.0000',
            'volume' => 100000,
        ]);

        EtfMetric::factory()->create([
            'etf_id' => $etf->id,
            'performance_range_type_id' => PerformanceRangeType::MAX,
            'total_return_percentage' => '-5.0000',
            'nav_erosion_percentage' => '-12.0000',
        ]);

        $snapshot = app(PortfolioSnapshotQuery::class)->getData($portfolio->id);

        $this->assertSame('Watch', $snapshot['nav_health']);
    }
}

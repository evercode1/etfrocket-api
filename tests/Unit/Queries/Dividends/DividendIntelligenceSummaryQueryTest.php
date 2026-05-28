<?php

namespace Tests\Unit\Queries\Dividends;

use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Security;
use App\Models\SecurityDividendHistory;
use App\Models\Status;
use App\Models\User;
use App\Queries\Dividends\DividendIntelligenceSummaryQuery;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DividendIntelligenceSummaryQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('security_dividend_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('users')->truncate();

        Carbon::setTestNow(Carbon::parse('2026-05-20'));
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('security_dividend_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('users')->truncate();

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_returns_zero_summary_when_portfolio_has_no_holdings(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $summary = app(DividendIntelligenceSummaryQuery::class)->getData($portfolio->id);

        $this->assertFalse($summary['has_holdings']);
        $this->assertSame(0, $summary['projected_monthly_income']);
        $this->assertSame(0, $summary['upcoming_weekly_events_count']);
        $this->assertNull($summary['forward_yield_percentage']);
        $this->assertNull($summary['dividend_growth_percentage']);
    }

    public function test_it_calculates_dividend_summary_for_current_holdings_using_recent_monthly_income_average(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $weeklySecurity = Security::factory()->create([
            'symbol' => 'NVII',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        $monthlySecurity = Security::factory()->create([
            'symbol' => 'JEPI',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 4,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $weeklySecurity->id,
            'transaction_type_id' => 1,
            'shares' => 10,
            'price_per_share' => 25,
            'transaction_date' => '2026-01-01',
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $monthlySecurity->id,
            'transaction_type_id' => 1,
            'shares' => 5,
            'price_per_share' => 50,
            'transaction_date' => '2026-01-01',
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $weeklySecurity->id,
            'dividend_amount' => '0.5000',
            'ex_dividend_date' => '2026-05-15',
            'payment_date' => '2026-05-16',
            'data_source_id' => 1,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $weeklySecurity->id,
            'dividend_amount' => '0.4000',
            'ex_dividend_date' => '2026-05-08',
            'payment_date' => '2026-05-09',
            'data_source_id' => 1,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $weeklySecurity->id,
            'dividend_amount' => '0.3000',
            'ex_dividend_date' => '2026-04-15',
            'payment_date' => '2026-04-16',
            'data_source_id' => 1,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $monthlySecurity->id,
            'dividend_amount' => '1.0000',
            'ex_dividend_date' => '2026-05-01',
            'payment_date' => '2026-05-02',
            'data_source_id' => 1,
        ]);

        $summary = app(DividendIntelligenceSummaryQuery::class)->getData($portfolio->id);

        $this->assertTrue($summary['has_holdings']);

        // May income:
        // Weekly Security: 10 shares * (.50 + .40) = 9.00
        // Monthly Security: 5 shares * 1.00 = 5.00
        // May total = 14.00
        //
        // April income:
        // Weekly Security: 10 shares * .30 = 3.00
        //
        // Average of non-zero recent monthly income rows = (14 + 3) / 2 = 8.50
        $this->assertSame(8.5, $summary['projected_monthly_income']);

        $this->assertSame(1, $summary['upcoming_weekly_events_count']);

        // Annualized income: 8.50 * 12 = 102
        // Cost basis: 250 + 250 = 500
        // Forward yield: 20.4%
        $this->assertSame(20.4, $summary['forward_yield_percentage']);
    }

    public function test_it_excludes_fully_sold_positions_from_summary(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $security = Security::factory()->create([
            'symbol' => 'SOLD',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'transaction_type_id' => 1,
            'shares' => 10,
            'price_per_share' => 25,
            'transaction_date' => '2026-01-01',
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'transaction_type_id' => 2,
            'shares' => 10,
            'price_per_share' => 30,
            'transaction_date' => '2026-02-01',
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'dividend_amount' => '1.0000',
            'ex_dividend_date' => '2026-05-01',
            'payment_date' => '2026-05-02',
            'data_source_id' => 1,
        ]);

        $summary = app(DividendIntelligenceSummaryQuery::class)->getData($portfolio->id);

        $this->assertFalse($summary['has_holdings']);
        $this->assertSame(0, $summary['projected_monthly_income']);
        $this->assertSame(0, $summary['upcoming_weekly_events_count']);
        $this->assertNull($summary['forward_yield_percentage']);
    }

    public function test_it_calculates_month_over_month_dividend_growth(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $security = Security::factory()->create([
            'symbol' => 'NVII',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'transaction_type_id' => 1,
            'shares' => 10,
            'price_per_share' => 25,
            'transaction_date' => '2026-01-01',
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'dividend_amount' => '1.0000',
            'ex_dividend_date' => '2026-03-15',
            'payment_date' => '2026-03-16',
            'data_source_id' => 1,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'dividend_amount' => '1.5000',
            'ex_dividend_date' => '2026-04-15',
            'payment_date' => '2026-04-16',
            'data_source_id' => 1,
        ]);

        $summary = app(DividendIntelligenceSummaryQuery::class)->getData($portfolio->id);

        $this->assertSame(50.0, $summary['dividend_growth_percentage']);
    }
}

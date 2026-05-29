<?php

namespace Tests\Unit\Queries\Dividends;

use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Security;
use App\Models\SecurityDetail;
use App\Models\SecurityDividendHistory;
use App\Models\Status;
use App\Models\User;
use App\Queries\Dividends\DividendIncomeTimelineQuery;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DividendIncomeTimelineQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-05-20'));

        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('security_dividend_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('security_dividend_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('users')->truncate();

        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_returns_empty_timeline_when_portfolio_has_no_holdings(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $timeline = app(DividendIncomeTimelineQuery::class)->getData($portfolio->id);

        $this->assertSame([], $timeline);
    }

    public function test_it_returns_five_month_projected_income_timeline_using_recent_monthly_income_average(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $weeklySecurity = Security::create([
            'symbol' => 'NVII',
            'status_id' => Status::ACTIVE,

        ]);

        SecurityDetail::factory()->create([
            'security_id' => $weeklySecurity->id,
            'distribution_frequency_id' => 2,
        ]);

        $monthlySecurity = Security::create([
            'symbol' => 'JEPI',
            'status_id' => Status::ACTIVE,

        ]);

        SecurityDetail::factory()->create([
            'security_id' => $monthlySecurity->id,
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

        $timeline = app(DividendIncomeTimelineQuery::class)->getData($portfolio->id);

        $this->assertCount(5, $timeline);

        $this->assertSame(
            ['May', 'Jun', 'Jul', 'Aug', 'Sep'],
            collect($timeline)->pluck('month')->toArray()
        );

        $this->assertSame(8.50, $timeline[0]['income']);

        $this->assertSame(8.56, $timeline[1]['income']);

        $this->assertSame(8.61, $timeline[2]['income']);

        $this->assertSame(8.67, $timeline[3]['income']);

        $this->assertSame(8.73, $timeline[4]['income']);
    }

    public function test_it_excludes_fully_sold_positions_from_timeline(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $security = Security::create([
            'symbol' => 'SOLD',
            'status_id' => Status::ACTIVE,

        ]);

        SecurityDetail::factory()->create([
            'security_id' => $security->id,
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

        $timeline = app(DividendIncomeTimelineQuery::class)->getData($portfolio->id);

        $this->assertSame([], $timeline);
    }

    public function test_it_returns_zero_income_when_holdings_have_no_dividend_history(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $security = Security::create([
            'symbol' => 'NODEV',
            'status_id' => Status::ACTIVE,
        ]);

        SecurityDetail::factory()->create([
            'security_id' => $security->id,
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

        $timeline = app(DividendIncomeTimelineQuery::class)->getData($portfolio->id);

        $this->assertCount(5, $timeline);

        foreach ($timeline as $month) {
            $this->assertSame(0.0, $month['income']);
        }
    }
}

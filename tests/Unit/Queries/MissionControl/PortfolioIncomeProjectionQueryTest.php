<?php

namespace Tests\Unit\Queries\MissionControl;

use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
use App\Models\Security;
use App\Models\SecurityDetail;
use App\Models\SecurityDividendHistory;
use App\Models\Status;
use App\Models\User;
use App\Queries\MissionControl\PortfolioIncomeProjectionQuery;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortfolioIncomeProjectionQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('security_dividend_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('users')->truncate();

        Carbon::setTestNow('2026-05-18');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('security_dividend_histories')->truncate();
        DB::table('securities')->truncate();
        DB::table('security_details')->truncate();
        DB::table('users')->truncate();

        parent::tearDown();
    }

    public function test_it_returns_empty_array_when_portfolio_has_no_holdings(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'Empty Portfolio',
        ]);

        $results = (new PortfolioIncomeProjectionQuery)->getData($portfolio->id);

        $this->assertSame([], $results);
    }

    public function test_it_projects_income_using_average_recent_dividends_for_weekly_etf(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'Income Rocket',
        ]);

        $security = Security::create([
            'symbol' => 'NVII',
            'status_id' => Status::ACTIVE,

        ]);

        SecurityDetail::factory()->create([
            'security_id' => $security->id,
            'distribution_frequency_id' => 2, // Weekly
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'transaction_type_id' => 1,
            'shares' => 10,
            'price_per_share' => 25,
            'transaction_date' => '2026-01-01',
        ]);

        foreach (
            [
                ['2026-05-01', '0.2000'],
                ['2026-04-01', '0.3000'],
                ['2026-03-01', '0.4000'],
                ['2026-02-01', '0.5000'],
            ] as [$date, $amount]
        ) {
            SecurityDividendHistory::factory()->create([
                'security_id' => $security->id,
                'dividend_amount' => $amount,
                'ex_dividend_date' => $date,
                'payment_date' => Carbon::parse($date)->addDay()->format('Y-m-d'),
                'data_source_id' => 1,
            ]);
        }

        $results = (new PortfolioIncomeProjectionQuery)->getData($portfolio->id, 3);

        // Average dividend = 0.35
        // Weekly monthly multiplier = 52 / 12
        // 10 shares * 0.35 * 4.333333 = 15.1667

        $this->assertCount(3, $results);

        $this->assertSame('May', $results[0]['month']);
        $this->assertSame(15.1667, $results[0]['income']);

        $this->assertSame('Jun', $results[1]['month']);
        $this->assertSame(15.1667, $results[1]['income']);

        $this->assertSame('Jul', $results[2]['month']);
        $this->assertSame(15.1667, $results[2]['income']);
    }

    public function test_it_uses_only_latest_four_dividends_for_average(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'Latest Four Portfolio',
        ]);

        $security = Security::create([
            'symbol' => 'AMDY',
            'status_id' => Status::ACTIVE,

        ]);

        SecurityDetail::factory()->create([
            'security_id' => $security->id,
            'distribution_frequency_id' => 2, // Weekly
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'transaction_type_id' => 1,
            'shares' => 10,
            'price_per_share' => 20,
            'transaction_date' => '2026-01-01',
        ]);

        foreach (
            [
                ['2026-05-01', '0.4000'],
                ['2026-04-01', '0.4000'],
                ['2026-03-01', '0.4000'],
                ['2026-02-01', '0.4000'],
                ['2026-01-01', '5.0000'],
            ] as [$date, $amount]
        ) {
            SecurityDividendHistory::factory()->create([
                'security_id' => $security->id,
                'dividend_amount' => $amount,
                'ex_dividend_date' => $date,
                'payment_date' => Carbon::parse($date)->addDay()->format('Y-m-d'),
                'data_source_id' => 1,
            ]);
        }

        $results = (new PortfolioIncomeProjectionQuery)->getData($portfolio->id, 1);

        // Latest four average = 0.4
        // Weekly monthly multiplier = 52 / 12
        // 10 shares * 0.4 * 4.333333 = 17.3333

        $this->assertCount(1, $results);
        $this->assertSame('May', $results[0]['month']);
        $this->assertSame(17.3333, $results[0]['income']);
    }

    public function test_it_subtracts_sell_transactions_from_projected_income(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'Sell Adjusted Portfolio',
        ]);

        $security = Security::create([
            'symbol' => 'CHPY',
            'status_id' => Status::ACTIVE,

        ]);

        SecurityDetail::factory()->create([
            'security_id' => $security->id,
            'distribution_frequency_id' => 2, // Weekly
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'transaction_type_id' => 1,
            'shares' => 10,
            'price_per_share' => 20,
            'transaction_date' => '2026-01-01',
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'transaction_type_id' => 2,
            'shares' => 4,
            'price_per_share' => 22,
            'transaction_date' => '2026-04-01',
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'dividend_amount' => '0.5000',
            'ex_dividend_date' => '2026-05-01',
            'payment_date' => '2026-05-02',
            'data_source_id' => 1,
        ]);

        $results = (new PortfolioIncomeProjectionQuery)->getData($portfolio->id, 1);

        // Shares after sell = 6
        // Weekly monthly multiplier = 52 / 12
        // 6 shares * 0.5 * 4.333333 = 13.0

        $this->assertCount(1, $results);
        $this->assertSame('May', $results[0]['month']);
        $this->assertSame(13.0, $results[0]['income']);
    }

    public function test_it_combines_income_from_multiple_securities(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'Multi Security Portfolio',
        ]);

        $firstSecurity = Security::create([
            'symbol' => 'NVII',
            'status_id' => Status::ACTIVE,

        ]);

        SecurityDetail::factory()->create([
            'security_id' => $firstSecurity->id,
            'distribution_frequency_id' => 2, // Weekly
        ]);

        $secondSecurity = Security::create([
            'symbol' => 'GOOY',
            'status_id' => Status::ACTIVE,
        ]);

        SecurityDetail::factory()->create([
            'security_id' => $secondSecurity->id,
            'distribution_frequency_id' => 2, // test_it_projects_income_using_average_recent_dividends_for_weekly_etf
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $firstSecurity->id,
            'transaction_type_id' => 1,
            'shares' => 10,
            'price_per_share' => 25,
            'transaction_date' => '2026-01-01',
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $secondSecurity->id,
            'transaction_type_id' => 1,
            'shares' => 20,
            'price_per_share' => 20,
            'transaction_date' => '2026-01-01',
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $firstSecurity->id,
            'dividend_amount' => '0.5000',
            'ex_dividend_date' => '2026-05-01',
            'payment_date' => '2026-05-02',
            'data_source_id' => 1,
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $secondSecurity->id,
            'dividend_amount' => '0.2500',
            'ex_dividend_date' => '2026-05-01',
            'payment_date' => '2026-05-02',
            'data_source_id' => 1,
        ]);

        $results = (new PortfolioIncomeProjectionQuery)->getData($portfolio->id, 2);

        // First Security: 10 * 0.5 * 4.333333 = 21.6667
        // Second Security: 20 * 0.25 * 4.333333 = 21.6667
        // Total = 43.3333

        $this->assertCount(2, $results);
        $this->assertSame(43.3333, $results[0]['income']);
        $this->assertSame(43.3333, $results[1]['income']);
    }

    public function test_it_handles_monthly_distribution_frequency(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'Monthly Security Portfolio',
        ]);

        $security = Security::create([
            'symbol' => 'QQQI',
            'status_id' => Status::ACTIVE,

        ]);

        SecurityDetail::factory()->create([
            'security_id' => $security->id,
            'distribution_frequency_id' => 4, // Monthly
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'transaction_type_id' => 1,
            'shares' => 10,
            'price_per_share' => 50,
            'transaction_date' => '2026-01-01',
        ]);

        SecurityDividendHistory::factory()->create([
            'security_id' => $security->id,
            'dividend_amount' => '1.0000',
            'ex_dividend_date' => '2026-05-01',
            'payment_date' => '2026-05-02',
            'data_source_id' => 1,
        ]);

        $results = (new PortfolioIncomeProjectionQuery)->getData($portfolio->id, 1);

        // Monthly multiplier = 1
        // 10 shares * 1.0 = 10.0

        $this->assertCount(1, $results);
        $this->assertSame('May', $results[0]['month']);
        $this->assertSame(10.0, $results[0]['income']);
    }

    public function test_it_ignores_holdings_without_dividend_history(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
            'portfolio_name' => 'No Dividend Portfolio',
        ]);

        $security = Security::create([
            'symbol' => 'NODEV',
            'status_id' => Status::ACTIVE,

        ]);

        SecurityDetail::factory()->create([
            'security_id' => $security->id,
            'distribution_frequency_id' => 9,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'security_id' => $security->id,
            'transaction_type_id' => 1,
            'shares' => 10,
            'price_per_share' => 20,
            'transaction_date' => '2026-01-01',
        ]);

        $results = (new PortfolioIncomeProjectionQuery)->getData($portfolio->id, 1);

        $this->assertCount(1, $results);
        $this->assertSame('May', $results[0]['month']);
        $this->assertSame(0.0, $results[0]['income']);
    }
}

<?php

namespace Tests\Unit\Queries\Dividends;

use App\Models\Etf;
use App\Models\EtfDividendHistory;
use App\Models\Portfolio;
use App\Models\PortfolioTransaction;
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
        DB::table('etf_dividend_histories')->truncate();
        DB::table('etfs')->truncate();
        DB::table('users')->truncate();
    }

    protected function tearDown(): void
    {
        DB::table('portfolio_transactions')->truncate();
        DB::table('portfolios')->truncate();
        DB::table('etf_dividend_histories')->truncate();
        DB::table('etfs')->truncate();
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

        $timeline = (new DividendIncomeTimelineQuery())->getData($portfolio->id);

        $this->assertSame([], $timeline);
    }

    public function test_it_returns_five_month_projected_income_timeline(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $weeklyEtf = Etf::factory()->create([
            'symbol' => 'NVII',
            'fund_name' => 'NVII Test ETF',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 2,
        ]);

        $monthlyEtf = Etf::factory()->create([
            'symbol' => 'JEPI',
            'fund_name' => 'JEPI Test ETF',
            'status_id' => Status::ACTIVE,
            'distribution_frequency_id' => 4,
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $weeklyEtf->id,
            'transaction_type_id' => 1,
            'shares' => 10,
            'price_per_share' => 25,
            'transaction_date' => '2026-01-01',
        ]);

        PortfolioTransaction::factory()->create([
            'portfolio_id' => $portfolio->id,
            'etf_id' => $monthlyEtf->id,
            'transaction_type_id' => 1,
            'shares' => 5,
            'price_per_share' => 50,
            'transaction_date' => '2026-01-01',
        ]);

        foreach (['0.2000', '0.3000', '0.4000', '0.5000'] as $index => $amount) {
            EtfDividendHistory::factory()->create([
                'etf_id' => $weeklyEtf->id,
                'dividend_amount' => $amount,
                'ex_dividend_date' => Carbon::parse('2026-05-15')->subDays($index)->toDateString(),
                'payment_date' => Carbon::parse('2026-05-16')->subDays($index)->toDateString(),
                'data_source_id' => 1,
            ]);
        }

        EtfDividendHistory::factory()->create([
            'etf_id' => $monthlyEtf->id,
            'dividend_amount' => '1.0000',
            'ex_dividend_date' => '2026-05-01',
            'payment_date' => '2026-05-02',
            'data_source_id' => 1,
        ]);

        $timeline = (new DividendIncomeTimelineQuery())->getData($portfolio->id);

        $this->assertCount(5, $timeline);

        $this->assertSame(
            ['May', 'Jun', 'Jul', 'Aug', 'Sep'],
            collect($timeline)->pluck('month')->toArray()
        );

        // Weekly ETF: avg .35 * 10 shares * 52/12 = 15.1667
        // Monthly ETF: avg 1.00 * 5 shares * 1 = 5.0000
        // Total rounded chart income = 20.17
        foreach ($timeline as $month) {
            $this->assertSame(20.17, $month['income']);
        }
    }

    public function test_it_excludes_fully_sold_positions_from_timeline(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $etf = Etf::factory()->create([
            'symbol' => 'SOLD',
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
            'transaction_type_id' => 2,
            'shares' => 10,
            'price_per_share' => 30,
            'transaction_date' => '2026-02-01',
        ]);

        EtfDividendHistory::factory()->create([
            'etf_id' => $etf->id,
            'dividend_amount' => '1.0000',
            'ex_dividend_date' => '2026-05-01',
            'payment_date' => '2026-05-02',
            'data_source_id' => 1,
        ]);

        $timeline = (new DividendIncomeTimelineQuery())->getData($portfolio->id);

        $this->assertSame([], $timeline);
    }

    public function test_it_returns_zero_income_when_holdings_have_no_dividend_history(): void
    {
        $user = User::factory()->create();

        $portfolio = Portfolio::factory()->create([
            'user_id' => $user->id,
            'status_id' => Status::ACTIVE,
        ]);

        $etf = Etf::factory()->create([
            'symbol' => 'NODEV',
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

        $timeline = (new DividendIncomeTimelineQuery())->getData($portfolio->id);

        $this->assertCount(5, $timeline);

        foreach ($timeline as $month) {
            $this->assertSame(0.0, $month['income']);
        }
    }
}
